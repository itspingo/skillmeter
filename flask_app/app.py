from flask import Flask, render_template, request, redirect, url_for, session, flash, json
import requests
import os
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

# Secret key for session management
app.secret_key = os.environ.get('FLASK_SECRET_KEY', os.urandom(24))

# API base URL
API_BASE_URL = os.environ.get('API_BASE_URL')

def make_api_request(method, endpoint, data=None, json=None, token=None):
    """Helper function to make API requests."""
    headers = {'Accept': 'application/json'}
    if token:
        headers['Authorization'] = f"Bearer {token}"

    print(f"--- API Request ---", flush=True)
    print(f"Method: {method}", flush=True)
    print(f"Endpoint: {API_BASE_URL}/{endpoint}", flush=True)
    print(f"Headers: {headers}", flush=True)
    if json:
        print(f"JSON Payload: {json}", flush=True)
    if data:
        print(f"Form Data: {data}", flush=True)
    print("--------------------", flush=True)

    try:
        response = requests.request(
            method,
            f"{API_BASE_URL}/{endpoint}",
            headers=headers,
            data=data,
            json=json,
            timeout=10
        )
        response.raise_for_status()
        
        print(f"--- API Response ---", flush=True)
        print(f"Status Code: {response.status_code}", flush=True)
        
        if response.status_code == 204:
            print("Response Body: (empty)", flush=True)
            print("---------------------", flush=True)
            return None, response.status_code
        
        response_json = response.json()
        print(f"Response Body: {response_json}", flush=True)
        print("---------------------", flush=True)
        return response_json, response.status_code

    except requests.exceptions.HTTPError as errh:
        print(f"--- API HTTP Error ---", flush=True)
        print(f"Status Code: {errh.response.status_code}", flush=True)
        try:
            error_details = errh.response.json()
            print(f"Error Body: {error_details}", flush=True)
        except ValueError:
            error_details = {'message': 'An unknown error occurred.'}
            print("Error Body: (Could not decode JSON)", flush=True)
        print("-----------------------", flush=True)
        return {'error': error_details, 'message': str(errh)}, errh.response.status_code
    except requests.exceptions.RequestException as e:
        print(f"--- API Connection Error ---", flush=True)
        print(f"Error: {e}", flush=True)
        print("---------------------------", flush=True)
        return {'error': 'Could not connect to the API server.'}, 503


    
def extract_user_data(response_data):
    """Extract user data from various possible API response structures."""
    if not response_data:
        return None, None
    
    user = response_data
    user_type = None
    
    if isinstance(response_data, dict):
        # Case 1: Direct user data
        if 'user_type' in response_data:
            if isinstance(response_data['user_type'], dict):
                user_type = response_data['user_type'].get('name')
            else:
                user_type = response_data['user_type']
            return response_data, user_type
        
        # Case 2: Data nested under 'data' key
        elif 'data' in response_data and isinstance(response_data['data'], dict):
            user_data = response_data['data']
            if 'user_type' in user_data:
                if isinstance(user_data['user_type'], dict):
                    user_type = user_data['user_type'].get('name')
                else:
                    user_type = user_data['user_type']
            return user_data, user_type
    
    return user, user_type    
    
    
    
@app.route('/')
def home():
    return render_template('home.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    print(f"\n>>> Received request for /login with method: {request.method}", flush=True)
    if request.method == 'POST':
        print(">>> Entering POST block for /login.", flush=True)
        email = request.form['email']
        password = request.form['password']
        
        response_data, status_code = make_api_request('POST', 'login', json={'email': email, 'password': password})
        
        if status_code == 200 and 'access_token' in response_data:
            session['access_token'] = response_data['access_token']
            flash('Login successful!', 'success')
            print(">>> Login SUCCESS. Redirecting to dashboard.", flush=True)
            return redirect(url_for('dashboard'))
        else:
            flash(response_data.get('message', 'Invalid credentials'), 'danger')
            print(">>> Login FAILED. Redirecting back to login.", flush=True)
            return redirect(url_for('login'))
            
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    print(f"\n>>> Received request for /register with method: {request.method}", flush=True)
    if request.method == 'POST':
        print(">>> Entering POST block for /register.", flush=True)
        # Note: Assuming 'name' is a single field. If you have first_name and last_name, concatenate them.
        # Assuming user_type_id is passed from the form.
        data = {
            'first_name': request.form['first_name'],
            'last_name': request.form['last_name'],
            'email': request.form['email'],
            'password': request.form['password'],
            'password_confirmation': request.form['password_confirmation'],
            'user_type': request.form['user_type'],
        }
        
        response_data, status_code = make_api_request('POST', 'register', json=data)
        
        if status_code in [200, 201] and 'access_token' in response_data:
            session['access_token'] = response_data['access_token']
            flash('Registration successful! You are now logged in.', 'success')
            print(">>> Registration SUCCESS. Redirecting to dashboard.", flush=True)
            return redirect(url_for('dashboard'))
        elif status_code == 422:
            errors = response_data.get('errors', {})
            for field, messages in errors.items():
                for message in messages:
                    flash(f"{field.replace('_', ' ').title()}: {message}", 'danger')
            print(f">>> Registration FAILED (422 Validation Error). Redirecting back to register.", flush=True)
        else:
            flash(response_data.get('message', 'Registration failed. Please try again.'), 'danger')
            print(f">>> Registration FAILED (Status: {status_code}). Redirecting back to register.", flush=True)
        
        return redirect(url_for('register'))

    return render_template('register.html')

@app.route('/dashboard')
def dashboard():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    user_data, status_code = make_api_request('GET', 'me', token=session['access_token'])
    
    if status_code == 200:
        user = user_data
        # Assuming the user object has a 'user_type' dictionary with a 'name' key
        if user.get('user_type') and user['user_type']['name'].lower() == 'recruiter':
            return redirect(url_for('recruiter_tests'))
        else:
            # For individuals, show their dashboard
            return redirect(url_for('individual_dashboard'))
    else:
        session.pop('access_token', None)
        flash('Your session has expired. Please log in again.', 'warning')
        return redirect(url_for('login'))

@app.route('/logout')
def logout():
    if 'access_token' in session:
        make_api_request('POST', 'logout', token=session['access_token'])
        session.pop('access_token', None)
        flash('You have been logged out successfully.', 'info')
    return redirect(url_for('login'))

@app.route('/forgot-password', methods=['GET', 'POST'])
def forgot_password():
    # This endpoint is not in the provided API guide, but keeping the implementation
    if request.method == 'POST':
        email = request.form['email']
        response_data, status_code = make_api_request('POST', 'forgot-password', data={'email': email})
        if status_code == 200:
            flash('A password reset link has been sent to your email.', 'success')
        else:
            flash(response_data.get('message', 'Could not process the request.'), 'danger')
        return redirect(url_for('forgot_password'))
    return render_template('forgot_password.html')

@app.route('/reset-password/<token>', methods=['GET', 'POST'])
def reset_password(token):
    # This endpoint is not in the provided API guide, but keeping the implementation
    if request.method == 'POST':
        data = {
            'token': token,
            'email': request.form['email'],
            'password': request.form['password'],
            'password_confirmation': request.form['password_confirmation']
        }
        response_data, status_code = make_api_request('POST', 'reset-password', data=data)
        if status_code == 200:
            flash('Your password has been reset successfully.', 'success')
            return redirect(url_for('login'))
        else:
            flash(response_data.get('message', 'Invalid or expired token.'), 'danger')
            return redirect(url_for('reset_password', token=token))
    return render_template('reset_password.html', token=token)

@app.route('/individual/dashboard')
def individual_dashboard():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Fetch attempts for the individual user
    attempts_data, attempts_status = make_api_request('GET', 'attempts', token=session['access_token'])
    if attempts_status != 200:
        flash('Could not fetch your test attempts.', 'danger')
        attempts = []
    else:
        attempts = attempts_data.get('data', [])
        
    return render_template('individual_dashboard.html', attempts=attempts)



@app.route('/recruiter/candidates')
def recruiter_candidates():
    if 'access_token' not in session:
        return redirect(url_for('login'))

    candidates_data, status_code = make_api_request('GET', 'users', token=session['access_token'])

    if status_code == 200:
        candidates = candidates_data.get('data', [])
    else:
        flash('Could not fetch candidates.', 'danger')
        candidates = []
    
    return render_template('recruiter_candidates.html', candidates=candidates)

@app.route('/recruiter/candidates/add', methods=['GET', 'POST'])
def add_candidate():
    if 'access_token' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        payload = {
            'first_name': request.form.get('first_name'),
            'last_name': request.form.get('last_name'),
            'email': request.form.get('email'),
            'password': request.form.get('password'),
            'user_type': 'candidate', # Assuming 2 is the ID for 'candidate'
            'active': int(request.form.get('is_active', 1))
        }
        # print("Adding candidate with payload:", payload, flush=True)

        response_data, status_code = make_api_request(
            'POST', 
            'users', 
            json=payload, 
            token=session['access_token']
        )

        if status_code == 201:
            flash('Candidate added successfully!', 'success')
            return redirect(url_for('recruiter_candidates'))
        else:
            error_message = response_data.get('message', 'Failed to add candidate.')
            flash(error_message, 'danger')
            return redirect(url_for('add_candidate'))

    return render_template('add_candidate.html')

@app.route('/recruiter/candidates/edit/<int:user_id>', methods=['GET', 'POST'])
def edit_candidate(user_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        payload = {
            'first_name': request.form.get('first_name'),
            'last_name': request.form.get('last_name'),
            'email': request.form.get('email'),
            'user_type': request.form.get('user_type'),
            'is_active': int(request.form.get('is_active', 0))
        }
        if request.form.get('password'):
            payload['password'] = request.form.get('password')

        response_data, status_code = make_api_request(
            'PUT', 
            f'users/{user_id}', 
            json=payload, 
            token=session['access_token']
        )

        if status_code == 200:
            flash('Candidate updated successfully!', 'success')
            return redirect(url_for('recruiter_candidates'))
        else:
            error_message = response_data.get('message', 'Failed to update candidate.')
            flash(error_message, 'danger')
            return redirect(url_for('edit_candidate', user_id=user_id))

    candidate_data, status_code = make_api_request('GET', f'users/{user_id}', token=session['access_token'])
    
    if status_code == 200:
        return render_template('edit_candidate.html', candidate=candidate_data)
    else:
        flash('Could not fetch candidate data.', 'danger')
        return redirect(url_for('recruiter_candidates'))

@app.route('/recruiter/candidates/delete/<int:user_id>', methods=['POST'])
def delete_candidate(user_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))

    # I'll assume the API endpoint to delete is DELETE /users/{user_id}
    _, status_code = make_api_request(
        'DELETE', 
        f'users/{user_id}', 
        token=session['access_token']
    )

    if status_code == 204 or status_code == 200:
        flash('Candidate deleted successfully!', 'success')
    else:
        flash('Failed to delete candidate.', 'danger')
    
    return redirect(url_for('recruiter_candidates'))




@app.route('/recruiter/tests')
def recruiter_tests():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    tests_data, tests_status = make_api_request('GET', 'tests', token=session['access_token'])
    print(tests_status)
    if tests_status != 200:
        flash('Could not fetch tests.', 'danger')
        tests = []
    else:
        tests = tests_data.get('data', [])
    
    return render_template('recruiter_tests.html', tests=tests)

@app.route('/tests/create', methods=['GET', 'POST'])
def create_test():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Get difficulty levels for dropdown
    difficulties_data, difficulties_status = make_api_request('GET', 'difficulties', token=session['access_token'])
    difficulties = difficulties_data.get('data', []) if difficulties_status == 200 else []
    
    if request.method == 'POST':
        data = {
            'name': request.form.get('name'),
            'description': request.form.get('description'),
            'time_limit': int(request.form.get('time_limit')),
            'is_public': 1 if request.form.get('is_public') else 0,
            'is_active': 1 if request.form.get('is_active') else 0,
            'pass_threshold': int(request.form.get('pass_threshold')),
            'show_score': 1 if request.form.get('show_score') else 0,
            'show_answers': 1 if request.form.get('show_answers') else 0,
            'randomize_questions': 1 if request.form.get('randomize_questions') else 0,
            'allow_backtracking': 1 if request.form.get('allow_backtracking') else 0,
            'instructions': request.form.get('instructions'),
            'base_lang': request.form.get('base_lang', 'en'),
            'active': 1 if request.form.get('active') else 0
        }
        
        
        response_data, status_code = make_api_request('POST', 'tests', json=data, token=session['access_token'])
        print('status_code: ', status_code)
        if status_code == 201:
            flash('Test created successfully!', 'success')
            return redirect(url_for('recruiter_tests'))
        else:
            flash(response_data.get('message', 'Error creating test.'), 'danger')
    
    return render_template('create_test.html', difficulties=difficulties)

@app.route('/tests/<int:test_id>')
def test_details(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    return render_template('test_details.html', test=test_data)

@app.route('/tests/<int:test_id>/edit', methods=['GET', 'POST'])
def edit_test(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Get difficulty levels for dropdown
    difficulties_data, difficulties_status = make_api_request('GET', 'difficulties', token=session['access_token'])
    difficulties = difficulties_data.get('data', []) if difficulties_status == 200 else []
    
    if request.method == 'POST':
        data = {
            'name': request.form.get('name'),
            'description': request.form.get('description'),
            'time_limit': int(request.form.get('time_limit')),
            'is_public': 1 if request.form.get('is_public') else 0,
            'is_active': 1 if request.form.get('is_active') else 0,
            'pass_threshold': int(request.form.get('pass_threshold')),
            'show_score': 1 if request.form.get('show_score') else 0,
            'show_answers': 1 if request.form.get('show_answers') else 0,
            'randomize_questions': 1 if request.form.get('randomize_questions') else 0,
            'allow_backtracking': 1 if request.form.get('allow_backtracking') else 0,
            'instructions': request.form.get('instructions'),
            'base_lang': request.form.get('base_lang', 'en'),
            'active': 1 if request.form.get('active') else 0
        }
        
        response_data, status_code = make_api_request('PUT', f'tests/{test_id}', json=data, token=session['access_token'])
        
        if status_code == 200:
            flash('Test updated successfully!', 'success')
            return redirect(url_for('test_details', test_id=test_id))
        else:
            error_msg = response_data.get('message', 'Error updating test.')
            flash(f"{error_msg} (Status: {status_code})", 'danger')
    
    # GET request - fetch test data
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    return render_template(
        'edit_test.html', 
        test=test_data,
        difficulties=difficulties,
        languages=['en', 'es', 'fr', 'de']
    )
    
    
@app.route('/tests/<int:test_id>/delete', methods=['POST'])
def delete_test(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    _, status_code = make_api_request('DELETE', f'tests/{test_id}', token=session['access_token'])
    
    if status_code == 204:
        flash('Test deleted successfully.', 'success')
    else:
        flash('Error deleting test.', 'danger')
    
    return redirect(url_for('recruiter_tests'))




@app.route('/tests/<int:test_id>/questions/add', methods=['GET', 'POST'])
def add_questions(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        question_ids = request.form.getlist('question_ids')
        data = {
            'questions': [{'question_id': qid} for qid in question_ids]
        }
        
        response_data, status_code = make_api_request('POST', f'tests/{test_id}/questions', json=data, token=session['access_token'])
        
        if status_code == 200:
            flash('Questions added successfully!', 'success')
            return redirect(url_for('test_details', test_id=test_id))
        else:
            flash(response_data.get('message', 'Error adding questions.'), 'danger')
    
    # Get available questions
    questions_data, questions_status = make_api_request('GET', 'questions', token=session['access_token'])
    questions = questions_data.get('data', []) if questions_status == 200 else []
    
    # Get current test questions to exclude them
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    if test_status == 200:
        current_question_ids = [q['id'] for q in test_data.get('data', {}).get('questions', [])]
        questions = [q for q in questions if q['id'] not in current_question_ids]
    
    return render_template('add_question.html', test_id=test_id, questions=questions)

@app.route('/tests/<int:test_id>/questions/<int:question_id>/remove', methods=['POST'])
def remove_question(test_id, question_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    _, status_code = make_api_request('DELETE', f'tests/{test_id}/questions/{question_id}', token=session['access_token'])
    
    if status_code == 200:
        flash('Question removed successfully.', 'success')
    else:
        flash('Error removing question.', 'danger')
    
    return redirect(url_for('test_details', test_id=test_id))


# Add these routes after the existing test routes

@app.route('/tests/<int:test_id>/invitations')
def test_invitations(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Get test details
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    # Get invitations for this test
    invitations_data, invitations_status = make_api_request('GET', 'test_invitations', token=session['access_token'])
    invitations = []
    
    if invitations_status == 200:
        # Filter invitations for this specific test
        invitations = [inv for inv in invitations_data.get('data', []) if inv.get('test_id') == test_id]
    else:
        flash('Could not fetch invitations.', 'warning')
    
    # Extract test data (could be nested under 'data' key or direct)
    test = test_data
    if isinstance(test_data, dict) and 'data' in test_data:
        test = test_data['data']
    
    return render_template('test_invitations.html', 
                         test=test, 
                         invitations=invitations)

@app.route('/tests/<int:test_id>/invitations/create', methods=['GET', 'POST'])
def create_invitation(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Get test details
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    # Extract test data
    test = test_data
    if isinstance(test_data, dict) and 'data' in test_data:
        test = test_data['data']
    
    if request.method == 'POST':
        data = {
            'test_id': test_id,
            'invitee_email': request.form.get('invitee_email'),
            'expires_at': request.form.get('expires_at'),
            'custom_message': request.form.get('custom_message'),
            'client_id': request.form.get('client_id'),
            'user_id': request.form.get('user_id'),
            'base_lang': request.form.get('base_lang', 'en')
        }
        
        response_data, status_code = make_api_request('POST', 'test_invitations', json=data, token=session['access_token'])
        
        if status_code == 201:
            flash('Invitation sent successfully!', 'success')
            return redirect(url_for('test_invitations', test_id=test_id))
        else:
            error_message = response_data.get('error', {}).get('message', 'Error creating invitation.')
            flash(error_message, 'danger')
    
    return render_template('create_invitation.html', test=test)


@app.route('/invitations/<int:invitation_id>/edit', methods=['GET', 'POST'])
def edit_invitation(invitation_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Get invitation details
    invitation_data, invitation_status = make_api_request('GET', f'test_invitations/{invitation_id}', token=session['access_token'])
    if invitation_status != 200:
        flash('Invitation not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    # Extract invitation data
    invitation = invitation_data
    if isinstance(invitation_data, dict) and 'data' in invitation_data:
        invitation = invitation_data['data']
    
    if request.method == 'POST':
        data = {
            'invitee_email': request.form.get('invitee_email'),
            'expires_at': request.form.get('expires_at'),
            'custom_message': request.form.get('custom_message'),
            'status': request.form.get('status'),
            'base_lang': request.form.get('base_lang', 'en'),
            'active': 1 if request.form.get('active') else 0
        }
        
        response_data, status_code = make_api_request('PUT', f'test_invitations/{invitation_id}', json=data, token=session['access_token'])
        
        if status_code == 200:
            flash('Invitation updated successfully!', 'success')
            return redirect(url_for('test_invitations', test_id=invitation.get('test_id')))
        else:
            error_message = response_data.get('error', {}).get('message', 'Error updating invitation.')
            flash(error_message, 'danger')
    
    return render_template('edit_invitation.html', invitation=invitation)



@app.route('/invitations/<int:invitation_id>/delete', methods=['POST'])
def delete_invitation(invitation_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    # Get invitation details first to redirect to the correct test
    invitation_data, invitation_status = make_api_request('GET', f'test_invitations/{invitation_id}', token=session['access_token'])
    test_id = None
    if invitation_status == 200:
        invitation = invitation_data
        if isinstance(invitation_data, dict) and 'data' in invitation_data:
            invitation = invitation_data['data']
        test_id = invitation.get('test_id') 
    
    response_data, status_code = make_api_request('DELETE', f'test_invitations/{invitation_id}', token=session['access_token'])
    
    if status_code == 200:
        flash('Invitation deleted successfully.', 'success')
    else:
        error_message = response_data.get('error', {}).get('message', 'Error deleting invitation.')
        flash(error_message, 'danger')
    
    if test_id:
        return redirect(url_for('test_invitations', test_id=test_id))
    return redirect(url_for('recruiter_tests'))


@app.route('/invitations/<int:invitation_id>/resend', methods=['POST'])
def resend_invitation(invitation_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    response_data, status_code = make_api_request('POST', f'test_invitations/{invitation_id}/resend', token=session['access_token'])
    
    if status_code == 200:
        flash('Invitation resent successfully!', 'success')
    else:
        error_message = response_data.get('error', {}).get('message', 'Error resending invitation.')
        flash(error_message, 'danger')
    
    # Get invitation details to redirect to the correct test
    invitation_data, invitation_status = make_api_request('GET', f'test_invitations/{invitation_id}', token=session['access_token'])
    if invitation_status == 200:
        invitation = invitation_data
        if isinstance(invitation_data, dict) and 'data' in invitation_data:
            invitation = invitation_data['data']
        test_id = invitation.get('test_id')
        return redirect(url_for('test_invitations', test_id=test_id))
    
    return redirect(url_for('recruiter_tests'))
    


#http://127.0.0.1:5000/test/take/math123token 

@app.route('/test/take/<token>')
def take_test(token):
    test_data, test_status = make_api_request('GET', f'tests/invitation/{token}')
    # print(test_data)  # Debug print
    # print(test_status)  # Debug print
    
    # Check for a successful status code AND the presence of the 'data' key
    if test_status != 200 :
        error_msg = test_data.get('message', 'Could not fetch the test. The link might be invalid or expired.')
        flash(error_msg, 'danger')
        return redirect(url_for('home'))

    test = test_data['data']

    # Parse the JSON string for each question and remove the answer
    for question in test.get('questions', []):
        if 'question_data' in question:
            try:
                question_info = json.loads(question['question_data'])
                # Delete the answer key to prevent it from being sent to the template
                if 'answer' in question_info:
                    del question_info['answer']
                question.update(question_info)
            except (json.JSONDecodeError, TypeError):
                print(f"Warning: Could not decode question_data for question ID {question.get('id')}")

    # Store test data in session for later use
    session['current_test'] = test
    session['test_token'] = token

    attempt_id = session.get('attempt_id')

    return render_template('take_test.html',
                           test=test,
                           attempt_id=attempt_id,
                           token=token)
    
    
@app.route('/test/start/<token>', methods=['POST'])
def start_test(token):
    # No auth required to start
    response_data, status_code = make_api_request('POST', f'tests/invitation/{token}/start')
    
    # print(f"Start test response: {response_data}")  # Debug print
    # print(f"Start test status: {status_code}")  # Debug print
    
    if status_code == 200:
        session['attempt_id'] = response_data.get('data', {}).get('id')
        session['test_started_at'] = datetime.now().isoformat()
        
        # Redirect to the test-taking page
        return redirect(url_for('take_test', token=token))
    else:
        error_msg = response_data.get('message', 'Could not start the test.')
        flash(error_msg, 'danger')
        # return redirect(url_for('home'))

# In your Flask application file
@app.route('/test/submit', methods=['POST'])
def submit_test():
    # Retrieve the attempt_id from the session, which was stored in the start_test function.
    attempt_id = session.get('attempt_id')

    # If attempt_id is missing, the session is invalid.
    if not attempt_id:
        flash('Your test session has expired. Please restart the test.', 'warning')
        return redirect(url_for('home'))

    # ... (rest of your response collection logic from the form) ...
    responses = []
    for key, value in request.form.items():
        if key.startswith('question_'):
            question_id = int(key.split('_')[1])
            responses.append({
                'question_id': question_id,
                'selected_option': value
            })

    # Prepare the submission data
    submission_data = {
        'responses': responses,
        'time_taken': None # Placeholder, your full code calculates this
    }

    # Make the API call using the correct attempt_id from the session.
    # The 'json' parameter is correctly passed to make_api_request.
    response_data, status_code = make_api_request(
        'POST',
        f'attempts/{attempt_id}/submit',
        json=submission_data
    )

    # ... (rest of your success/error handling) ...
    if status_code == 200:
        flash('Test submitted successfully!', 'success')
        # Redirect to a success page or dashboard
        return redirect(url_for('dashboard'))
    else:
        error_msg = response_data.get('message', 'There was an error submitting your test.')
        flash(error_msg, 'danger')
        return redirect(url_for('dashboard'))
    
# @app.route('/test/submit', methods=['POST'])
# def submit_test():
#     attempt_id = request.form.get('attempt_id')
    
#     if not attempt_id:
#         flash('Your session is invalid. Please start the test again.', 'warning')
#         return redirect(url_for('home'))
    
#     responses = []
    
#     for key, value in request.form.items():
#         if key.startswith('question_'):
#             question_id = key.split('_')[1]
#             responses.append({
#                 'question_id': int(question_id),
#                 'selected_option': value
#             })
    
#     # Calculate time taken if available
#     time_taken = None
#     if 'test_started_at' in session:
#         try:
#             started_at = datetime.fromisoformat(session['test_started_at'])
#             time_taken = (datetime.now() - started_at).total_seconds()
#         except:
#             pass
    
#     submission_data = {
#         'responses': responses,
#         'time_taken': time_taken
#     }
    
#     # Use the token from session if available
#     token = session.get('test_token', '')
    
#     response_data, status_code = make_api_request(
#         'POST',
#         f'attempts/{attempt_id}/submit',
#         json=submission_data,
#         token=session.get('access_token', '')
#     )

#     # Clean up session
#     session.pop('attempt_id', None)
#     session.pop('test_started_at', None)
#     session.pop('current_test', None)
#     session.pop('test_token', None)
    
#     if status_code == 200:
#         flash('Test submitted successfully!', 'success')
#         return redirect(url_for('dashboard'))
#     else:
#         error_msg = response_data.get('message', 'There was an error submitting your test.')
#         flash(error_msg, 'danger')
#         return redirect(url_for('dashboard'))

@app.route('/test/active/<token>')
def take_test_active(token):
    if 'attempt_id' not in session or token != session.get('test_token'):
        flash('Please start the test first.', 'warning')
        return redirect(url_for('start_test', token=token))
    
    test_data = session.get('current_test', {})
    attempt_id = session.get('attempt_id')
    
    return render_template('take_test.html', test=test_data, attempt_id=attempt_id)


@app.route('/tests/<int:test_id>/questions')
def test_questions(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    tests_data, tests_status = make_api_request('GET', 'questions', token=session['access_token'])
    print(tests_status)
    if tests_status != 200:
        flash('Could not fetch tests.', 'danger')
        questions = []
    else:
        questions = tests_data.get('data', [])
    
    return render_template('test_questions.html', questions=questions)



# Recruiter Questions CRUD
@app.route('/recruiter/questions')
def recruiter_questions():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    questions_data, questions_status = make_api_request('GET', 'questions', token=session['access_token'])
    
    if questions_status != 200:
        flash('Could not fetch questions.', 'danger')
        questions = []
    else:
        questions = questions_data.get('data', [])
    
    return render_template('recruiter_questions.html', questions=questions)

@app.route('/questions/create', methods=['GET', 'POST'])
def create_question():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        options = []
        for key, value in request.form.items():
            if key.startswith('options'):
                index = key.split('[')[1].split(']')[0]
                field = key.split('[')[2].split(']')[0]
                while len(options) <= int(index):
                    options.append({})
                options[int(index)][field] = value

        for option in options:
            option['is_correct'] = 1 if 'is_correct' in option else 0

        data = {
            'question_text': request.form.get('question_text'),
            'question_type_id': int(request.form.get('question_type_id')),
            'question_category_id': int(request.form.get('question_category_id')),
            'difficulty_level_id': int(request.form.get('difficulty_level_id')),
            'points': int(request.form.get('points')),
            'options': options
        }
        
        response_data, status_code = make_api_request('POST', 'questions', json=data, token=session['access_token'])
        
        if status_code == 201:
            flash('Question created successfully!', 'success')
            return redirect(url_for('recruiter_questions'))
        else:
            flash(response_data.get('message', 'Error creating question.'), 'danger')

    # Fetch necessary data for dropdowns
    types_data, _ = make_api_request('GET', 'question-types', token=session['access_token'])
    categories_data, _ = make_api_request('GET', 'question-categories', token=session['access_token'])
    difficulty_data, _ = make_api_request('GET', 'difficulty-levels', token=session['access_token'])

    return render_template(
        'create_question.html',
        question_types=types_data.get('data', []),
        categories=categories_data.get('data', []),
        difficulty_levels=difficulty_data.get('data', [])
    )

@app.route('/questions/<int:question_id>/edit', methods=['GET', 'POST'])
def edit_question(question_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        options = []
        for key, value in request.form.items():
            if key.startswith('options'):
                index = key.split('[')[1].split(']')[0]
                field = key.split('[')[2].split(']')[0]
                while len(options) <= int(index):
                    options.append({})
                options[int(index)][field] = value

        for option in options:
            option['is_correct'] = 1 if 'is_correct' in option else 0

        data = {
            'question_text': request.form.get('question_text'),
            'question_type_id': int(request.form.get('question_type_id')),
            'question_category_id': int(request.form.get('question_category_id')),
            'difficulty_level_id': int(request.form.get('difficulty_level_id')),
            'points': int(request.form.get('points')),
            'options': options
        }
        
        response_data, status_code = make_api_request('PUT', f'questions/{question_id}', json=data, token=session['access_token'])
        
        if status_code == 200:
            flash('Question updated successfully!', 'success')
            return redirect(url_for('recruiter_questions'))
        else:
            flash(response_data.get('message', 'Error updating question.'), 'danger')

    # Fetch question data and data for dropdowns
    question_data, status = make_api_request('GET', f'questions/{question_id}', token=session['access_token'])
    if status != 200:
        flash('Question not found.', 'danger')
        return redirect(url_for('recruiter_questions'))

    types_data, _ = make_api_request('GET', 'question-types', token=session['access_token'])
    categories_data, _ = make_api_request('GET', 'question-categories', token=session['access_token'])
    difficulty_data, _ = make_api_request('GET', 'difficulty-levels', token=session['access_token'])

    return render_template(
        'edit_question.html',
        question=question_data,
        question_types=types_data.get('data', []),
        categories=categories_data.get('data', []),
        difficulty_levels=difficulty_data.get('data', [])
    )

@app.route('/questions/<int:question_id>/delete', methods=['POST'])
def delete_question(question_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    _, status_code = make_api_request('DELETE', f'questions/{question_id}', token=session['access_token'])
    
    if status_code == 200:
        flash('Question deleted successfully.', 'success')
    else:
        flash('Error deleting question.', 'danger')
    
    return redirect(url_for('recruiter_questions'))


if __name__ == '__main__':
    app.run(debug=True, port=5000)
