import cv2
import os
import numpy as np
import time
import csv
from datetime import datetime
import requests
import pyaudio
import pygetwindow as gw
import mss
from fpdf import FPDF
import matplotlib.pyplot as plt
import seaborn as sns
from PIL import Image
import threading
import torch
from ultralytics import YOLO
import mediapipe as mp
import json
from database_manager import DatabaseManager  # Added for JSON handling

# ======================
# CONFIGURATION SETTINGS
# ======================
# Video Settings
RESOLUTION_WIDTH = 640
RESOLUTION_HEIGHT = 480
FRAME_RATE = 30

# Face Detection & Eye Tracking (MediaPipe)
FACE_DETECTION_CONFIDENCE = 0.6
EYE_ASPECT_RATIO_THRESHOLD = 0.23
LOOKING_AWAY_THRESHOLD_HORIZONTAL = 0.1
LOOKING_AWAY_THRESHOLD_VERTICAL = 0.08
ATTENTION_CHECK_INTERVAL = 3

# Mobile Detection (YOLOv8)
PHONE_CONFIDENCE_THRESHOLD = 0.45
ALERT_TEXT = "⚠️ MOBILE DETECTED! ⚠️"
ALERT_COLOR = (0, 0, 255)  # Red

# Audio Settings
AUDIO_THRESHOLD = 1000
SILENCE_THRESHOLD = 3

# Browser Monitoring
FORBIDDEN_APPS = ["Chrome", "Firefox", "Edge", "Safari", "Opera", "Brave", "Vivaldi"]
SCREENSHOT_INTERVAL = 5

# Logging Configuration
LOG_FOLDER = "proctoring_logs"
CSV_FILENAME = "cheating_events.csv"
SCREENSHOTS_FOLDER = "screenshots"
REPORTS_FOLDER = "reports"  # Added for organized reporting
LOG_INTERVAL = 3

# ======================
# BROWSER ACTIVITY TRACKER - NEW
# ======================
class BrowserActivityTracker:
    def __init__(self, db_manager=None):
        self.activities = []
        self.current_session_start = None
        self.last_window = None
        self.db_manager = db_manager
        
    def log_activity(self, window_title, duration=0):
        activity = {
            'timestamp': datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            'browser': window_title,
            'duration': round(duration, 1)
        }
        self.activities.append(activity)
        if self.db_manager and self.db_manager.conn:
            self.db_manager.log_browser_activity(activity)
        
    def start_session(self, window_title):
        if self.current_session_start and self.last_window:
            duration = time.time() - self.current_session_start
            self.log_activity(self.last_window, duration)
        
        self.current_session_start = time.time()
        self.last_window = window_title
        
    def end_session(self):
        if self.current_session_start and self.last_window:
            duration = time.time() - self.current_session_start
            self.log_activity(self.last_window, duration)
        self.current_session_start = None
        self.last_window = None
        
    def get_activities(self):
        return self.activities
        
    def save_to_json(self, filename):
        with open(filename, 'w') as f:
            json.dump(self.activities, f, indent=4)

# ======================
# MEDIAPIPE COMPONENTS (ENHANCED)
# ======================
class MediaPipeTracker:
    def __init__(self):
        self.mp_face_mesh = mp.solutions.face_mesh
        self.face_mesh = self.mp_face_mesh.FaceMesh(
            max_num_faces=1,
            refine_landmarks=True,
            min_detection_confidence=FACE_DETECTION_CONFIDENCE
        )
        self.mp_drawing = mp.solutions.drawing_utils
        self.drawing_spec = self.mp_drawing.DrawingSpec(thickness=1, circle_radius=1)

        # Eye landmark indices for better tracking
        self.LEFT_EYE_IDXS = [33, 7, 163, 144, 145, 153, 154, 155, 133, 173, 157, 158, 159, 160, 161, 246]
        self.RIGHT_EYE_IDXS = [362, 382, 381, 380, 373, 374, 390, 249, 263, 466, 388, 387, 386, 385, 384, 398]
        
        # Eye contour points for drawing
        self.LEFT_EYE_CONTOUR = [33, 7, 163, 144, 145, 153, 154, 155, 133, 173, 157, 158, 159, 160, 161, 246]
        self.RIGHT_EYE_CONTOUR = [362, 382, 381, 380, 373, 374, 390, 249, 263, 466, 388, 387, 386, 385, 384, 398]

    def eye_aspect_ratio(self, landmarks, eye_indices):
        # Vertical landmarks
        p2_p6 = np.linalg.norm(landmarks[eye_indices[1]] - landmarks[eye_indices[5]])
        p3_p5 = np.linalg.norm(landmarks[eye_indices[2]] - landmarks[eye_indices[4]])
        # Horizontal landmark
        p1_p4 = np.linalg.norm(landmarks[eye_indices[0]] - landmarks[eye_indices[3]])
        
        if p1_p4 == 0:
            return 0.0
            
        ear = (p2_p6 + p3_p5) / (2.0 * p1_p4)
        return ear

    def draw_eye_contour(self, frame, landmarks, eye_indices, color=(0, 0, 255)):
        """Draw eye contour with specified color"""
        h, w, _ = frame.shape
        points = []
        for idx in eye_indices:
            x = int(landmarks[idx].x * w)
            y = int(landmarks[idx].y * h)
            points.append((x, y))
        
        if len(points) > 0:
            points = np.array(points, np.int32)
            cv2.polylines(frame, [points], True, color, 2)
            cv2.fillPoly(frame, [points], (*color, 30))  # Semi-transparent fill

    def analyze_frame(self, frame):
        frame.flags.writeable = False
        frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = self.face_mesh.process(frame_rgb)
        frame.flags.writeable = True

        face_detected = False
        ear = 0.0
        gaze_direction = "CENTER"
        eyes_looking_away = False

        if results.multi_face_landmarks:
            face_detected = True
            face_landmarks = results.multi_face_landmarks[0]
            
            # Draw basic face mesh
            self.mp_drawing.draw_landmarks(
                image=frame,
                landmark_list=face_landmarks,
                connections=self.mp_face_mesh.FACEMESH_TESSELATION,
                landmark_drawing_spec=None,
                connection_drawing_spec=self.mp_drawing.DrawingSpec(color=(0,255,0), thickness=1, circle_radius=1)
            )

            landmarks = face_landmarks.landmark
            landmarks_array = np.array([(lm.x, lm.y, lm.z) for lm in landmarks])
            
            # Calculate Eye Aspect Ratio
            left_ear = self.eye_aspect_ratio(landmarks_array, self.LEFT_EYE_IDXS)
            right_ear = self.eye_aspect_ratio(landmarks_array, self.RIGHT_EYE_IDXS)
            ear = (left_ear + right_ear) / 2.0

            # Gaze Detection
            h, w, _ = frame.shape
            nose_x_coord = landmarks_array[1, 0]
            nose_y_coord = landmarks_array[1, 1]

            x_rel = nose_x_coord - 0.5
            y_rel = nose_y_coord - 0.5

            if x_rel < -LOOKING_AWAY_THRESHOLD_HORIZONTAL:
                gaze_direction = "LEFT"
                eyes_looking_away = True
            elif x_rel > LOOKING_AWAY_THRESHOLD_HORIZONTAL:
                gaze_direction = "RIGHT"
                eyes_looking_away = True
            elif y_rel < -LOOKING_AWAY_THRESHOLD_VERTICAL:
                gaze_direction = "UP"
                eyes_looking_away = True
            elif y_rel > LOOKING_AWAY_THRESHOLD_VERTICAL:
                gaze_direction = "DOWN"
                eyes_looking_away = True
            else:
                gaze_direction = "CENTER"
                eyes_looking_away = False

            # Draw red eye contours when looking away
            if eyes_looking_away or ear < EYE_ASPECT_RATIO_THRESHOLD:
                self.draw_eye_contour(frame, landmarks, self.LEFT_EYE_CONTOUR, (0, 0, 255))
                self.draw_eye_contour(frame, landmarks, self.RIGHT_EYE_CONTOUR, (0, 0, 255))

        return face_detected, ear, gaze_direction, frame

# ======================
# ENHANCED BROWSER MONITOR WITH ACTIVITY TRACKING
# ======================
class BrowserMonitor:
    def __init__(self, forbidden_apps=None, db_manager=None):
        self.sct = mss.mss()
        self.forbidden_apps = forbidden_apps or FORBIDDEN_APPS
        self.last_screenshot_time = 0
        self.last_active_window = "Unknown"
        self.current_forbidden_app = None
        self.activity_tracker = BrowserActivityTracker(db_manager)  # NEW: Activity tracker
        self.window_change_time = time.time()
        
    def get_active_window(self):
        try:
            active = gw.getActiveWindow()
            current_title = active.title if active else "Unknown"
            
            # Track window changes for activity logging
            if current_title != self.last_active_window:
                if self.last_active_window != "Unknown":
                    duration = time.time() - self.window_change_time
                    self.activity_tracker.log_activity(self.last_active_window, duration)
                self.window_change_time = time.time()
                
            self.last_active_window = current_title
            return current_title
        except Exception as e:
            return "Unknown"
    
    def is_forbidden_app_active(self):
        active_window = self.get_active_window().lower()
        for app in self.forbidden_apps:
            if app.lower() in active_window:
                self.current_forbidden_app = app
                return True
        self.current_forbidden_app = None
        return False
    
    def get_current_app_name(self):
        return self.last_active_window if self.last_active_window != "Unknown" else "Unknown"
    
    def get_browser_activities(self):  # NEW
        return self.activity_tracker.get_activities()
    
    def save_screenshot(self, filename):
        try:
            monitor = self.sct.monitors[1]
            sct_img = self.sct.grab(monitor)
            img = Image.frombytes("RGB", sct_img.size, sct_img.bgra, "raw", "BGRX")
            img.save(filename, 'JPEG', quality=85)
            self.last_screenshot_time = time.time()
            return True
        except Exception as e:
            print(f"Screenshot error: {e}")
            return False

# ======================
# ENHANCED REPORT GENERATOR WITH BROWSER ACTIVITY TABLE
# ======================
class ReportGenerator:
    def __init__(self, events, browser_activities, user_name="Student", exam_name="Exam", session_duration=0):
        self.events = events
        self.browser_activities = browser_activities  # NEW: Browser activities
        self.user_name = user_name
        self.exam_name = exam_name
        self.session_duration = session_duration
        self.pdf = FPDF()
        self.pdf.set_auto_page_break(auto=True, margin=15)
        
    def generate(self, filename="proctoring_report.pdf"):
        self.pdf.add_page()
        self._add_header()
        self._add_summary()
        self._add_browser_activities()  # NEW: Add browser activities section
        self._add_analytics()
        self._add_timeline()
        self._add_event_details()
        self._add_screenshots()
        try:
            self.pdf.output(filename)
            return filename
        except Exception as e:
            print(f"Error generating PDF: {e}")
            return None
    
    def _add_header(self):
        self.pdf.set_font("Arial", "B", 20)
        self.pdf.cell(0, 15, "AI Proctoring System Report", 0, 1, "C")
        self.pdf.set_font("Arial", "B", 14)
        self.pdf.cell(0, 10, f"Student: {self.user_name}", 0, 1, "C")
        self.pdf.cell(0, 10, f"Exam: {self.exam_name}", 0, 1, "C")
        self.pdf.set_font("Arial", "", 12)
        self.pdf.cell(0, 10, f"Session Duration: {self.session_duration:.1f} minutes", 0, 1, "C")
        self.pdf.cell(0, 10, f"Generated on: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", 0, 1)
        self.pdf.ln(5)
    
    def _add_summary(self):
        self.pdf.set_font("Arial", "B", 16)
        self.pdf.cell(0, 10, "Executive Summary", 0, 1)
        
        event_types = {}
        for event in self.events:
            etype = event['type']
            event_types[etype] = event_types.get(etype, 0) + 1
        
        total_events = len(self.events)
        risk_score = self._calculate_risk_score(event_types, total_events)
        
        if risk_score < 30:
            risk_level, risk_color = "Low", (0, 128, 0)
        elif risk_score < 70:
            risk_level, risk_color = "Moderate", (255, 128, 0)
        else:
            risk_level, risk_color = "High", (255, 0, 0)
        
        self.pdf.set_font("Arial", "", 12)
        summary_text = f"""This report presents an analysis of the proctoring session for {self.user_name}.
The session recorded a total of {total_events} potential integrity events over {self.session_duration:.1f} minutes.
Browser activities were monitored with {len(self.browser_activities)} window changes detected.
Based on the frequency and severity of the detected events, the overall risk assessment is:"""
        self.pdf.multi_cell(0, 6, summary_text)
        
        self.pdf.ln(5)
        self.pdf.set_fill_color(*risk_color)
        self.pdf.set_text_color(255, 255, 255)
        self.pdf.set_font("Arial", "B", 14)
        self.pdf.cell(0, 10, f"Risk Assessment: {risk_level} ({risk_score}/100)", 1, 1, "C", True)
        self.pdf.set_text_color(0, 0, 0)
        self.pdf.ln(10)
    
    def _add_browser_activities(self):  # NEW: Browser activity table
        """Add browser activity tracking section"""
        self.pdf.set_font("Arial", "B", 16)
        self.pdf.cell(0, 10, "Detected Browser Activities", 0, 1)
        self.pdf.ln(5)
        
        if not self.browser_activities:
            self.pdf.set_font("Arial", "", 12)
            self.pdf.cell(0, 10, "No browser activities detected during the session.", 0, 1)
            self.pdf.ln(10)
            return
        
        # Create table headers
        self.pdf.set_fill_color(240, 240, 240)
        self.pdf.set_font("Arial", "B", 10)
        headers = ["Timestamp", "Browser", "Duration (s)"]
        col_widths = [50, 100, 30]
        
        for i, header in enumerate(headers):
            self.pdf.cell(col_widths[i], 8, header, 1, 0, 'C', True)
        self.pdf.ln()
        
        # Add activity rows
        self.pdf.set_font("Arial", "", 9)
        for i, activity in enumerate(self.browser_activities[:20]):  # Show first 20 activities
            fill = i % 2 == 1
            self.pdf.set_fill_color(240, 248, 255) if fill else self.pdf.set_fill_color(255, 255, 255)
            
            cells = [
                activity.get('timestamp', ''),
                activity.get('browser', 'Unknown')[:45] + '...' if len(activity.get('browser', 'Unknown')) > 45 else activity.get('browser', 'Unknown'),
                str(activity.get('duration', 0))
            ]
            
            for i, cell in enumerate(cells):
                self.pdf.cell(col_widths[i], 6, str(cell), 1, 0, fill=fill)
            self.pdf.ln()
        
        if len(self.browser_activities) > 20:
            self.pdf.set_font("Arial", "I", 10)
            self.pdf.cell(0, 10, f"... and {len(self.browser_activities) - 20} more activities", 0, 1)
        
        self.pdf.ln(10)
    
    def _calculate_risk_score(self, event_types, total_events):
        if total_events == 0: return 0
        weights = {"MOBILE_DETECTED": 10, "NO_FACE_DETECTED": 7, "ATTENTION_CHANGE": 3, "VOICE_DETECTED": 5, "FORBIDDEN_APP": 8, "MOBILE_DETECTED_END": 2}
        weighted_sum = sum(count * weights.get(etype, 1) for etype, count in event_types.items())
        return round(min(100, (weighted_sum / 3)))
    
    def _add_analytics(self):
        self.pdf.set_font("Arial", "B", 14)
        self.pdf.cell(0, 10, "Analytics", 0, 1)
        
        event_counts = {}
        for event in self.events:
            etype = event['type']
            event_counts[etype] = event_counts.get(etype, 0) + 1
            
        if event_counts:
            pie_filename = os.path.join(LOG_FOLDER, 'event_pie.png')
            plt.figure(figsize=(8, 5))
            plt.pie(list(event_counts.values()), labels=list(event_counts.keys()), autopct='%1.1f%%', startangle=90)
            plt.title('Event Type Distribution')
            plt.savefig(pie_filename, dpi=100, bbox_inches='tight')
            plt.close()
            
            self.pdf.image(pie_filename, x=55, w=100)
            self.pdf.ln(85)
            try: os.remove(pie_filename)
            except: pass
    
    def _add_timeline(self):
        pass

    def _add_event_details(self):
        self.pdf.add_page()
        self.pdf.set_font("Arial", "B", 14)
        self.pdf.cell(0, 10, "Detailed Event Log", 0, 1)
        
        self.pdf.set_fill_color(240, 240, 240)
        self.pdf.set_font("Arial", "B", 8)
        headers = ["Timestamp", "Event Type", "Face", "Attention", "Voice", "App Name", "Details"]
        col_widths = [35, 25, 15, 25, 15, 25, 45]
        for i, header in enumerate(headers):
            self.pdf.cell(col_widths[i], 7, header, 1, 0, 'C', True)
        self.pdf.ln()
        
        self.pdf.set_font("Arial", "", 7)
        for i, event in enumerate(self.events[:100]):
            fill = i % 2 == 1
            self.pdf.set_fill_color(240, 248, 255) if fill else self.pdf.set_fill_color(255, 255, 255)
            
            app_name = event.get('app_name', 'N/A')
            if event.get('forbidden_app') and app_name == 'N/A':
                app_name = 'Forbidden'
            
            cells = [
                event.get('timestamp', ''),
                event.get('type', ''),
                "Yes" if event.get('face_detected') else "No",
                event.get('attention', 'N/A'),
                "Yes" if event.get('voice_detected') else "No",
                app_name[:20] + '...' if len(app_name) > 20 else app_name,
                f"Conf: {event.get('confidence', 0):.2f}" if 'confidence' in event else f"Dur: {event.get('duration', 0):.1f}s"
            ]
            for i, cell in enumerate(cells):
                self.pdf.cell(col_widths[i], 6, str(cell), 1, 0, fill=fill)
            self.pdf.ln()

    def _add_screenshots(self):
        screenshots = [e['screenshot'] for e in self.events if e.get('screenshot') and os.path.exists(e['screenshot'])]
        if not screenshots: return
            
        self.pdf.add_page()
        self.pdf.set_font("Arial", "B", 16)
        self.pdf.cell(0, 10, "Evidence Screenshots", 0, 1)
        self.pdf.ln(5)
        
        for i in range(min(4, len(screenshots))):
            try:
                self.pdf.image(screenshots[i], w=180)
                self.pdf.ln(2)
            except Exception as e:
                self.pdf.cell(0, 10, f"Error loading image: {e}", 0, 1)

# ======================
# AUDIO MONITOR
# ======================
class AudioMonitor:
    def __init__(self, threshold=1000, silence_threshold=3):
        self.audio = pyaudio.PyAudio()
        self.stream = None
        self.sound_detected = False
        self.voice_detected = False
        self.prev_voice_detected = False
        self.threshold = threshold
        self.silence_threshold = silence_threshold
        self.last_sound_time = 0
        self.voice_activity = False
        
        self.voice_start_time = 0
        self.voice_duration = 0
        
        self.buffer_size = 3  # Reduced for better performance
        self.rms_buffer = []
        self.zcr_buffer = []
        
    def start(self):
        try:
            self.stream = self.audio.open(
                format=pyaudio.paInt16,
                channels=1,
                rate=16000,
                input=True,
                frames_per_buffer=1024,
                stream_callback=self.callback
            )
            self.stream.start_stream()
            return True
        except Exception as e:
            print(f"Audio error: {e}")
            return False
    
    def callback(self, in_data, frame_count, time_info, status):
        audio_data = np.frombuffer(in_data, dtype=np.int16)
        filtered_data = audio_data - np.mean(audio_data)
        max_amplitude = np.max(np.abs(filtered_data))
        self.sound_detected = max_amplitude > self.threshold
        
        current_time = time.time()
        rms = np.sqrt(np.mean(np.square(filtered_data)))
        zcr = np.sum(np.diff(np.sign(filtered_data)) != 0) / len(filtered_data)
        
        self.rms_buffer.append(rms)
        self.zcr_buffer.append(zcr)
        
        if len(self.rms_buffer) > self.buffer_size: self.rms_buffer.pop(0)
        if len(self.zcr_buffer) > self.buffer_size: self.zcr_buffer.pop(0)
        
        avg_rms = np.mean(self.rms_buffer) if self.rms_buffer else 0
        avg_zcr = np.mean(self.zcr_buffer) if self.zcr_buffer else 0
        
        is_voice = (avg_rms > 500 and 0.05 < avg_zcr < 0.25)
        
        if is_voice:
            if not self.voice_activity:
                self.voice_activity = True
                self.voice_start_time = current_time
            self.voice_duration = current_time - self.voice_start_time
            self.last_sound_time = current_time
        else:
            if self.voice_activity and (current_time - self.last_sound_time > 0.5):
                self.voice_activity = False
        
        if self.voice_activity:
            self.voice_detected = True
        elif current_time - self.last_sound_time > self.silence_threshold:
            self.voice_detected = False
        
        return (in_data, pyaudio.paContinue)
    
    def stop(self):
        if self.stream:
            self.stream.stop_stream()
            self.stream.close()
        self.audio.terminate()

# ======================
# UTILITY FUNCTIONS (ENHANCED)
# ======================
def exception_handler(func):
    def wrapper(*args, **kwargs):
        try:
            return func(*args, **kwargs)
        except Exception as e:
            print(f"FATAL EXCEPTION in {func.__name__}: {e}")
            import traceback
            traceback.print_exc()
    return wrapper


def download_model(url, filename):
    if not os.path.exists(filename):
        print(f"Downloading {filename}...")
        try:
            r = requests.get(url, stream=True)
            r.raise_for_status()
            with open(filename, 'wb') as f:
                for chunk in r.iter_content(chunk_size=8192):
                    f.write(chunk)
            print("Download complete!")
            return True
        except Exception as e:
            print(f"Download error: {e}")
            return False
    return True

def load_yolo_model():
    model_file = 'yolov8n.pt'
    if not download_model('https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n.pt', model_file):
        return None, []
    try:
        model = YOLO(model_file)
        phone_classes = [i for i, name in model.names.items() if 'cell phone' in name]
        if not phone_classes: phone_classes = [67]
        print(f"Phone class found: {model.names[phone_classes[0]]} (ID {phone_classes[0]})")
        return model, phone_classes
    except Exception as e:
        print(f"Error loading YOLO model: {e}")
        return None, []

def detect_phones(model, classes, frame, confidence_threshold):
    if model is None: return [], 0.0
    try:
        results = model.predict(frame, conf=confidence_threshold, classes=classes, verbose=False)
        detections = [{'bbox': list(map(int, box.xyxy[0])), 'confidence': box.conf.item()} for r in results for box in r.boxes]
        max_confidence = max(d['confidence'] for d in detections) if detections else 0.0
        return detections, max_confidence
    except Exception as e:
        return [], 0.0

# ======================
# MAIN PROCTORING SYSTEM WITH BROWSER TRACKING
# ======================
class ProctorSystem:
    def __init__(self):
        self.running = False
        self.frame_lock = threading.Lock()
        self.current_frame = None
        self.processed_frame = None
        
        # self.csv_path = setup_logging()
        self.db_manager = DatabaseManager()
        self.events = []
        
        self.mediapipe_tracker = MediaPipeTracker()
        self.audio_monitor = AudioMonitor()
        self.browser_monitor = BrowserMonitor(db_manager=self.db_manager)  # Enhanced with activity tracking
        self.phone_model, self.phone_classes = load_yolo_model()
        
        self.cap = None
        self.attention_state = "FOCUSED"
        self.face_present = False
        self.mobile_detected = False
        self.mobile_alert_frames = 0
        
        self.counters = {'mobile': 0, 'no_face': 0, 'attention': 0, 'audio': 0, 'browser': 0}
        self.timers = {'last_phone_log': 0, 'phone_start': None, 'last_no_face_log': 0, 'no_face_start': None, 'last_browser_log': 0}
        self.start_time = time.time()

    def start(self):
        print("Starting Enhanced AI Proctoring System...")
        self.cap = cv2.VideoCapture(0)
        if not self.cap.isOpened():
            print("Error: Camera not found!")
            return False
        
        # Optimize camera settings
        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, RESOLUTION_WIDTH)
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, RESOLUTION_HEIGHT)
        self.cap.set(cv2.CAP_PROP_FPS, FRAME_RATE)
        self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)  # Reduce buffer for real-time processing
        
        self.audio_monitor.start()
        
        self.running = True
        self.start_time = time.time()
        
        self.capture_thread = threading.Thread(target=self.capture_loop, daemon=True)
        self.analysis_thread = threading.Thread(target=self.analysis_loop, daemon=True)
        
        self.capture_thread.start()
        self.analysis_thread.start()
        
        print("System started! Press 'q' in the camera window to quit.")
        self.display_loop()
        return True

    def stop(self):
        if not self.running: return
        print("Stopping system...")
        self.running = False
        
        if self.capture_thread.is_alive(): self.capture_thread.join(timeout=2)
        if self.analysis_thread.is_alive(): self.analysis_thread.join(timeout=2)
            
        if self.cap: self.cap.release()
        self.audio_monitor.stop()
        cv2.destroyAllWindows()
        
        self.generate_report()
        
        session_duration = (time.time() - self.start_time) / 60  # in minutes
        print("\n" + "="*50 + "\nSESSION SUMMARY\n" + "="*50)
        print(f"Total Runtime: {session_duration:.1f} minutes")
        print(f"Total Events Logged: {len(self.events)}")
        for event_type, count in self.counters.items():
            print(f"{event_type.title()} Events: {count}")
        print("="*50 + "\nSystem stopped. Goodbye!")
    
    def capture_loop(self):
        while self.running:
            ret, frame = self.cap.read()
            if not ret:
                time.sleep(0.01)
                continue
            with self.frame_lock:
                self.current_frame = frame.copy()
            time.sleep(1/FRAME_RATE)  # Control frame rate
    
    def analysis_loop(self):
        while self.running:
            with self.frame_lock:
                if self.current_frame is None:
                    time.sleep(0.01)
                    continue
                frame = self.current_frame.copy()
            
            current_time = time.time()
            processed_frame = frame.copy()

            # Face & Eye Tracking
            face_present, ear, gaze_direction, processed_frame = self.mediapipe_tracker.analyze_frame(processed_frame)
            self.face_present = face_present

            if not face_present:
                if self.timers['no_face_start'] is None: 
                    self.timers['no_face_start'] = current_time
                if current_time - self.timers['last_no_face_log'] > LOG_INTERVAL:
                    self.log_event("NO_FACE_DETECTED", screenshot_frame=processed_frame)
                    self.timers['last_no_face_log'] = current_time
                    self.counters['no_face'] += 1
            else:
                self.timers['no_face_start'] = None

            if face_present:
                new_state = "FOCUSED"
                if ear < EYE_ASPECT_RATIO_THRESHOLD: 
                    new_state = "EYES_CLOSED"
                elif gaze_direction != "CENTER": 
                    new_state = f"LOOKING_{gaze_direction}"
                if new_state != self.attention_state:
                    self.attention_state = new_state
                    self.log_event("ATTENTION_CHANGE", screenshot_frame=processed_frame)
                    self.counters['attention'] += 1
            else:
                self.attention_state = "NO_FACE"

            # Mobile Phone Detection with Enhanced Alert
            detections, max_conf = detect_phones(self.phone_model, self.phone_classes, frame, PHONE_CONFIDENCE_THRESHOLD)
            self.mobile_detected = len(detections) > 0
            
            if detections:
                if self.timers['phone_start'] is None: 
                    self.timers['phone_start'] = current_time
                if current_time - self.timers['last_phone_log'] > LOG_INTERVAL:
                    self.log_event("MOBILE_DETECTED", confidence=max_conf, screenshot_frame=processed_frame)
                    self.timers['last_phone_log'] = current_time
                    self.counters['mobile'] += 1
                
                # Draw detection boxes and alert
                for d in detections:
                    bbox = d['bbox']
                    cv2.rectangle(processed_frame, (bbox[0], bbox[1]), (bbox[2], bbox[3]), ALERT_COLOR, 3)
                    
                # Mobile alert animation
                self.mobile_alert_frames = 30  # Show alert for 30 frames
                
            elif self.timers['phone_start'] is not None:
                self.log_event("MOBILE_DETECTED_END", duration=current_time - self.timers['phone_start'])
                self.timers['phone_start'] = None
            
            # Audio & Browser Checks
            if self.audio_monitor.voice_detected and not self.audio_monitor.prev_voice_detected:
                self.log_event("VOICE_DETECTED", screenshot_frame=processed_frame)
                self.counters['audio'] += 1
            self.audio_monitor.prev_voice_detected = self.audio_monitor.voice_detected

            if self.browser_monitor.is_forbidden_app_active() and current_time - self.timers['last_browser_log'] > LOG_INTERVAL:
                app_name = self.browser_monitor.get_current_app_name()
                self.log_event("FORBIDDEN_APP", screenshot_frame=processed_frame, is_browser_event=True, app_name=app_name)
                self.timers['last_browser_log'] = current_time
                self.counters['browser'] += 1
                
            self.update_display_panel(processed_frame)
            
            with self.frame_lock:
                self.processed_frame = processed_frame.copy()
            
            time.sleep(0.01)  # Small delay to prevent overprocessing

    def log_event(self, event_type, **kwargs):
        event = {
            'type': event_type,
            'timestamp': datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            'face_detected': self.face_present,
            'attention': self.attention_state,
            'voice_detected': self.audio_monitor.voice_detected,
            'forbidden_app': self.browser_monitor.is_forbidden_app_active(),
            **kwargs
        }
        
        screenshot_frame = event.pop('screenshot_frame', None)
        is_browser_event = event.pop('is_browser_event', False)
        
        if screenshot_frame is not None:
            ts = int(time.time())
            filename = f"{event_type}_{ts}.jpg"
            path = os.path.join(LOG_FOLDER, SCREENSHOTS_FOLDER, filename)
            if is_browser_event:
                self.browser_monitor.save_screenshot(path)
            else:
                cv2.imwrite(path, screenshot_frame)
            event['screenshot'] = path
        
        # log_event_to_csv(self.csv_path, event)
        if self.db_manager.conn: # Check karein ke connection hai
            self.db_manager.log_event(event)
        self.events.append(event)
        
    def update_display_panel(self, frame):
        # Enhanced status panel with better visibility
        panel_height = 140
        cv2.rectangle(frame, (5, 5), (300, panel_height), (0, 0, 0), -1)
        cv2.rectangle(frame, (5, 5), (300, panel_height), (255, 255, 255), 2)
        
        # Status indicators
        attention_color = (0, 255, 0) if self.attention_state == "FOCUSED" else (0, 0, 255)
        audio_color = (0, 0, 255) if self.audio_monitor.voice_detected else (0, 255, 0)
        browser_color = (0, 0, 255) if self.browser_monitor.is_forbidden_app_active() else (0, 255, 0)
        mobile_color = (0, 0, 255) if self.mobile_detected else (0, 255, 0)
        
        # Display information
        cv2.putText(frame, f"Face: {'DETECTED' if self.face_present else 'NOT FOUND'}", (10, 25), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0) if self.face_present else (0, 0, 255), 1)
        cv2.putText(frame, f"Attention: {self.attention_state}", (10, 45), cv2.FONT_HERSHEY_SIMPLEX, 0.5, attention_color, 1)
        cv2.putText(frame, f"Audio: {'VOICE DETECTED' if self.audio_monitor.voice_detected else 'SILENT'}", (10, 65), cv2.FONT_HERSHEY_SIMPLEX, 0.5, audio_color, 1)
        cv2.putText(frame, f"Mobile: {'DETECTED!' if self.mobile_detected else 'NOT DETECTED'}", (10, 85), cv2.FONT_HERSHEY_SIMPLEX, 0.5, mobile_color, 1)
        
        if self.browser_monitor.is_forbidden_app_active():
            app_name = self.browser_monitor.get_current_app_name()
            cv2.putText(frame, f"App: {app_name[:20]}{'...' if len(app_name) > 20 else ''} (FORBIDDEN!)", (10, 105), cv2.FONT_HERSHEY_SIMPLEX, 0.4, browser_color, 1)
        else:
            cv2.putText(frame, "App: ALLOWED", (10, 105), cv2.FONT_HERSHEY_SIMPLEX, 0.5, browser_color, 1)
            
        cv2.putText(frame, f"Events: {len(self.events)}", (10, 125), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255,255,255), 1)
        
        # Mobile detection alert
        if self.mobile_alert_frames > 0:
            self.mobile_alert_frames -= 1
            # Flashing red alert
            alpha = 0.3 if self.mobile_alert_frames % 10 < 5 else 0.1
            overlay = frame.copy()
            cv2.rectangle(overlay, (0, 0), (frame.shape[1], frame.shape[0]), (0, 0, 255), -1)
            cv2.addWeighted(overlay, alpha, frame, 1 - alpha, 0, frame)
            
            # Alert text
            text_size = cv2.getTextSize(ALERT_TEXT, cv2.FONT_HERSHEY_SIMPLEX, 1.5, 3)[0]
            text_x = (frame.shape[1] - text_size[0]) // 2
            text_y = frame.shape[0] // 2
            cv2.putText(frame, ALERT_TEXT, (text_x, text_y), cv2.FONT_HERSHEY_SIMPLEX, 1.5, (255, 255, 255), 3)

    def display_loop(self):
        while self.running:
            with self.frame_lock:
                if self.processed_frame is None:
                    time.sleep(0.01)
                    continue
                frame = self.processed_frame.copy()
            
            cv2.imshow('Enhanced AI Proctoring System', frame)
            
            key = cv2.waitKey(1) & 0xFF
            if key == ord('q'):
                self.stop()
                break
    
    def generate_report(self):
        if not self.events:
            print("No events to report.")
            return
            
        session_duration = (time.time() - self.start_time) / 60  # in minutes
        browser_activities = self.browser_monitor.get_browser_activities()
        
        print(f"Generating report for {len(self.events)} events and {len(browser_activities)} browser activities...")
        report_filename = f"report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
        report_path = os.path.join(LOG_FOLDER, REPORTS_FOLDER, report_filename)
        
        report = ReportGenerator(
            self.events, 
            browser_activities, 
            user_name="Student Name", 
            exam_name="Sample Exam",
            session_duration=session_duration
        )
        
        generated_path = report.generate(report_path)
        if generated_path:
            print(f"Report generated: {os.path.abspath(generated_path)}")
            
            # Also save browser activities as JSON
            json_path = os.path.join(LOG_FOLDER, REPORTS_FOLDER, f"browser_activities_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
            self.browser_monitor.activity_tracker.save_to_json(json_path)
            
            return generated_path
        else:
            print("Failed to generate report.")
            return None

# ======================
# MAIN
# ======================
@exception_handler
def main():
    system = ProctorSystem()
    system.start()

if __name__ == "__main__":
    main()