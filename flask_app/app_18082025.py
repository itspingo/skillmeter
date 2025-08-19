from flask import Flask, render_template, request, redirect, url_for, session, flash
import requests
import os
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

# Secret key for session management
app.secret_key = os.environ.get('FLASK_SECRET_KEY', os.urandom(24))

# API base URL
API_BASE_URL = os.environ.get('API_BASE_URL', 'http://127.0.0.1:8001/api')

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
            'name': request.form['name'],
            'email': request.form['email'],
            'password': request.form['password'],
            'password_confirmation': request.form['password_confirmation'],
            'user_type_id': int(request.form['user_type_id'])
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

@app.route('/recruiter/tests')
def recruiter_tests():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    tests_data, tests_status = make_api_request('GET', 'tests', token=session['access_token'])
    if tests_status != 200:
        flash('Could not fetch tests.', 'danger')
        tests = []
    else:
        tests = tests_data.get('data', [])
    
    return render_template('recruiter_tests.html', tests=tests)

@app.route('/recruiter/candidates')
def recruiter_candidates():
    if 'access_token' not in session:
        return redirect(url_for('login'))

    # Assuming the API returns a list of users (candidates)
    # I'll assume the endpoint is 'users' and the API will filter for the recruiter
    candidates_data, status_code = make_api_request('GET', 'users', token=session['access_token'])

    if status_code == 200:
        # The API response is expected to be a list of user objects
        candidates = candidates_data.get('data', []) if isinstance(candidates_data, dict) else candidates_data
    else:
        flash('Could not fetch candidates.', 'danger')
        candidates = []
    
    return render_template('recruiter_candidates.html', candidates=candidates)

@app.route('/recruiter/candidates/add', methods=['GET', 'POST'])
def add_candidate():
    if 'access_token' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        # Construct the data payload from the form
        payload = {
            'first_name': request.form.get('first_name'),
            'last_name': request.form.get('last_name'),
            'email': request.form.get('email'),
            'password': request.form.get('password'),
            'user_type': request.form.get('user_type'),
            'is_active': int(request.form.get('is_active', 0))
        }

        # I'll assume the API endpoint to create a user is POST /users
        response_data, status_code = make_api_request(
            'POST', 
            'users', 
            json=payload, 
            token=session['access_token']
        )

        if status_code == 201 or status_code == 200:
            flash('Candidate added successfully!', 'success')
            return redirect(url_for('recruiter_candidates'))
        else:
            error_message = response_data.get('message', 'Failed to add candidate.')
            flash(error_message, 'danger')
            # It's better to re-render the form with errors if possible,
            # but for now, we'll redirect.
            return redirect(url_for('add_candidate'))

    # For GET request, just show the form
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
        # Only include password if a new one is provided
        if request.form.get('password'):
            payload['password'] = request.form.get('password')

        # I'll assume the API endpoint to update is PUT /users/{user_id}
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

    # GET request: Fetch existing candidate data
    # I'll assume the API endpoint is GET /users/{user_id}
    candidate_data, status_code = make_api_request('GET', f'users/{user_id}', token=session['access_token'])
    
    if status_code == 200:
        candidate = candidate_data.get('data', candidate_data)
        return render_template('edit_candidate.html', candidate=candidate)
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





@app.route('/tests/create', methods=['GET', 'POST'])
def create_test():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        data = {
            'title': request.form.get('title'),
            'description': request.form.get('description'),
            'duration_minutes': request.form.get('duration_minutes')
        }
        
        response_data, status_code = make_api_request('POST', 'tests', json=data, token=session['access_token'])
        
        if status_code == 201:
            flash('Test created successfully!', 'success')
            return redirect(url_for('recruiter_tests'))
        else:
            flash(response_data.get('message', 'Error creating test.'), 'danger')
    
    return render_template('create_test.html')

@app.route('/tests/<int:test_id>')
def test_details(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    return render_template('test_details.html', test=test_data.get('data', {}))

@app.route('/tests/<int:test_id>/edit', methods=['GET', 'POST'])
def edit_test(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        data = {
            'title': request.form.get('title'),
            'description': request.form.get('description'),
            'duration_minutes': request.form.get('duration_minutes')
        }
        
        response_data, status_code = make_api_request('PUT', f'tests/{test_id}', json=data, token=session['access_token'])
        
        if status_code == 200:
            flash('Test updated successfully!', 'success')
            return redirect(url_for('test_details', test_id=test_id))
        else:
            flash(response_data.get('message', 'Error updating test.'), 'danger')
    
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    return render_template('edit_test.html', test=test_data.get('data', {}))

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

@app.route('/test/take/<token>')
def take_test(token):
    # This endpoint uses a public invitation token, so no session auth is needed
    test_data, test_status = make_api_request('GET', f'tests/invitation/{token}')
    if test_status != 200:
        flash('Could not fetch the test. The link might be invalid or expired.', 'danger')
        return redirect(url_for('home'))

    return render_template('take_test.html', test=test_data.get('data', {}), token=token)

@app.route('/test/start/<token>', methods=['POST'])
def start_test(token):
    # No auth required to start
    response_data, status_code = make_api_request('POST', f'tests/invitation/{token}/start')

    if status_code == 200:
        # The user might need to log in to get a token to submit
        # For simplicity, let's assume the user is already logged in or the submission is handled differently
        session['attempt_id'] = response_data.get('data', {}).get('id')
        # Redirect to the test-taking page, which should be protected
        return redirect(url_for('take_test_active'))
    else:
        flash(response_data.get('message', 'Could not start the test.'), 'danger')
        return redirect(url_for('home'))

@app.route('/test/submit', methods=['POST'])
def submit_test():
    if 'access_token' not in session or 'attempt_id' not in session:
        flash('Your session is invalid. Please start the test again.', 'warning')
        return redirect(url_for('login'))

    attempt_id = session['attempt_id']
    responses = []
    for key, value in request.form.items():
        if key.startswith('question_'):
            question_id = key.split('_')[1]
            # This assumes single-choice answers. Adapt if multi-choice is possible.
            responses.append({
                'question_id': int(question_id),
                'selected_option_ids': [int(value)] 
            })

    response_data, status_code = make_api_request(
        'POST',
        f'attempts/{attempt_id}/submit',
        json={'responses': responses},
        token=session['access_token']
    )

    if status_code == 200:
        session.pop('attempt_id', None)
        flash('Test submitted successfully!', 'success')
        return redirect(url_for('dashboard'))
    else:
        flash(response_data.get('message', 'There was an error submitting your test.'), 'danger')
        return redirect(url_for('take_test_active')) # Or wherever the active test page is

if __name__ == '__main__':
    app.run(debug=True, port=5000)
