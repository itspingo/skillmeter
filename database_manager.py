import mysql.connector
from datetime import datetime

class DatabaseManager:
    def __init__(self, host="localhost", user="root", password="", database="proctoring_db"):
        """Database se connect karne ki koshish karta hai."""
        try:
            self.conn = mysql.connector.connect(
                host=host,
                user=user,
                password=password,  # XAMPP mein by default password khali hota hai
                database=database
            )
            self.cursor = self.conn.cursor()
            print(f"MySQL Database '{database}' se connection kamyab.")
        except mysql.connector.Error as err:
            print(f"Database Error: {err}")
            print("\nKya aapne XAMPP mein Apache aur MySQL start kiya hai?")
            print(f"Kya 'proctoring_db' naam ka database maujood hai?")
            self.conn = None

    def log_event(self, event_data):
        """Ek event ko 'events' table mein save karta hai."""
        if not self.conn:
            return

        sql = ''' INSERT INTO events(timestamp, event_type, confidence, duration, face_detected, attention_state, voice_detected, forbidden_app, app_name, screenshot_path)
                  VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s) '''
        
        params = (
            event_data.get('timestamp', datetime.now().strftime("%Y-%m-%d %H:%M:%S")),
            event_data.get('type'),
            event_data.get('confidence', 0),
            event_data.get('duration', 0),
            event_data.get('face_detected', False),
            event_data.get('attention'),
            event_data.get('voice_detected', False),
            event_data.get('forbidden_app', False),
            event_data.get('app_name', 'N/A'),
            event_data.get('screenshot', '')
        )
        
        self.cursor.execute(sql, params)
        self.conn.commit()
        print(f"Event '{event_data.get('type')}' database mein log ho gaya.")

    def log_browser_activity(self, activity_data):
        """Browser activity ko 'browser_activity' table mein save karta hai."""
        if not self.conn:
            return
            
        sql = ''' INSERT INTO browser_activity(timestamp, browser_title, duration)
                  VALUES(%s, %s, %s) '''
        params = (
            activity_data.get('timestamp'),
            activity_data.get('browser'),
            activity_data.get('duration')
        )
        self.cursor.execute(sql, params)
        self.conn.commit()

    def __del__(self):
        """Object delete hone par connection band kar deta hai."""
        if hasattr(self, 'conn') and self.conn and self.conn.is_connected():
            self.cursor.close()
            self.conn.close()
            print("Database connection band kar diya gaya.")