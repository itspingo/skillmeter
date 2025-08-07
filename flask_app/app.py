from flask import Flask, render_template, request, redirect, url_for, session, flash
import requests
import os
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__, template_folder=os.path.abspath('../theme'))

# Secret key for session management
app.secret_key = os.environ.get('FLASK_SECRET_KEY', os.urandom(24))

# API base URL
API_BASE_URL = os.environ.get('API_BASE_URL', 'http://127.0.0.1:8001/api')

def make_api_request(method, endpoint, data=None, json=None, token=None):
    """Helper function to make API requests."""
    headers = {'Accept': 'application/json'}
    if token:
        headers['Authorization'] = f"Bearer {token}"
    
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
        return response.json(), response.status_code
    except requests.exceptions.HTTPError as errh:
        try:
            error_details = errh.response.json()
        except ValueError:
            error_details = {'message': 'An unknown error occurred.'}
        return {'error': error_details, 'message': str(errh)}, errh.response.status_code
    except requests.exceptions.RequestException as e:
        return {'error': 'Could not connect to the API server.'}, 503

@app.route('/')
def home():
    if 'access_token' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']
        
        response_data, status_code = make_api_request('POST', 'auth/login', data={'email': email, 'password': password})
        
        if status_code == 200 and 'access_token' in response_data:
            session['access_token'] = response_data['access_token']
            flash('Login successful!', 'success')
            return redirect(url_for('dashboard'))
        else:
            flash(response_data.get('error', {}).get('message', 'Invalid credentials'), 'danger')
            return redirect(url_for('login'))
            
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        data = {
            'first_name': request.form['first_name'],
            'last_name': request.form['last_name'],
            'email': request.form['email'],
            'password': request.form['password'],
            'password_confirmation': request.form['password_confirmation'],
            'user_type': request.form['user_type']
        }
        
        response_data, status_code = make_api_request('POST', 'auth/register', data=data)
        
        if status_code in [200, 201] and 'access_token' in response_data:
            session['access_token'] = response_data['access_token']
            flash('Registration successful! You are now logged in.', 'success')
            return redirect(url_for('dashboard'))
        elif status_code == 422:
            errors = response_data.get('error', {}).get('errors', {})
            for field, messages in errors.items():
                for message in messages:
                    flash(f"{field.replace('_', ' ').title()}: {message}", 'danger')
        else:
            flash(response_data.get('error', 'Registration failed. Please try again.'), 'danger')
        
        return redirect(url_for('register'))

    return render_template('register.html')

@app.route('/dashboard')
def dashboard():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    user_data, status_code = make_api_request('GET', 'auth/me', token=session['access_token'])
    
    if status_code == 200:
        user = user_data
        if user.get('user_type') and user['user_type']['name'] == 'recruiter':
            return redirect(url_for('recruiter_tests'))
        else:
            tests_data, tests_status = make_api_request('GET', 'tests', token=session['access_token'])
            tests = tests_data.get('data', []) if tests_status == 200 else []
            return render_template('individual_dashboard.html', user=user, tests=tests)
    else:
        session.pop('access_token', None)
        flash('Your session has expired. Please log in again.', 'warning')
        return redirect(url_for('login'))

@app.route('/logout')
def logout():
    if 'access_token' in session:
        make_api_request('POST', 'auth/logout', token=session['access_token'])
        session.pop('access_token', None)
        flash('You have been logged out successfully.', 'info')
    return redirect(url_for('login'))

@app.route('/forgot-password', methods=['GET', 'POST'])
def forgot_password():
    if request.method == 'POST':
        email = request.form['email']
        response_data, status_code = make_api_request('POST', 'auth/forgot-password', data={'email': email})
        if status_code == 200:
            flash('A password reset link has been sent to your email.', 'success')
        else:
            flash(response_data.get('error', {}).get('message', 'Could not process the request.'), 'danger')
        return redirect(url_for('forgot_password'))
    return render_template('forgot_password.html')

@app.route('/reset-password/<token>', methods=['GET', 'POST'])
def reset_password(token):
    if request.method == 'POST':
        data = {
            'token': token,
            'email': request.form['email'],
            'password': request.form['password'],
            'password_confirmation': request.form['password_confirmation']
        }
        response_data, status_code = make_api_request('POST', 'auth/reset-password', data=data)
        if status_code == 200:
            flash('Your password has been reset successfully.', 'success')
            return redirect(url_for('login'))
        else:
            flash(response_data.get('error', {}).get('message', 'Invalid or expired token.'), 'danger')
            return redirect(url_for('reset_password', token=token))
    return render_template('reset_password.html', token=token)

# @app.route('/tests/create', methods=['GET', 'POST'])
# def create_test():
#     if 'access_token' not in session:
#         return redirect(url_for('login'))

#     if request.method == 'POST':
#         data = {
#             'title': request.form['title'],
#             'description': request.form['description']
#         }
#         response_data, status_code = make_api_request('POST', 'tests', json=data, token=session['access_token'])
#         if status_code == 201:
#             flash('Test created successfully!', 'success')
#             return redirect(url_for('dashboard'))
#         else:
#             flash(response_data.get('error', {}).get('message', 'Error creating test.'), 'danger')
#             return redirect(url_for('create_test'))

#     return render_template('create_test.html')

# @app.route('/test/<int:test_id>')
# def test_details(test_id):
#     if 'access_token' not in session:
#         return redirect(url_for('login'))

#     test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
#     if test_status != 200:
#         flash('Could not fetch test details.', 'danger')
#         return redirect(url_for('dashboard'))

#     # Assuming the API returns attempts and invitations related to the test
#     return render_template('test_details.html', test=test_data.get('data', {}))

@app.route('/test/take/<token>')
def take_test(token):
    start_data, start_status = make_api_request('POST', f'tests/invitation/{token}/start')
    if start_status not in [200, 201]:
        flash('Could not start the test. The link might be invalid or expired.', 'danger')
        return redirect(url_for('home'))
    
    attempt_id = start_data.get('data', {}).get('id')

    test_data, test_status = make_api_request('GET', f'tests/invitation/{token}')
    if test_status != 200:
        flash('Could not fetch the test. The link might be invalid or expired.', 'danger')
        return redirect(url_for('home'))

    test = test_data
    return render_template('take_test.html', test=test, token=token, attempt_id=attempt_id)

@app.route('/test/submit/<attempt_id>', methods=['POST'])
def submit_test(attempt_id):
    if 'access_token' not in session:
        flash('Your session has expired. Please log in again.', 'warning')
        return redirect(url_for('login'))

    responses = []
    for key, value in request.form.items():
        if key.startswith('question_'):
            question_id = key.split('_')[1]
            responses.append({
                'question_id': int(question_id),
                'selected_options': [int(value)]
            })

    response_data, status_code = make_api_request(
        'POST',
        f'attempts/{attempt_id}/submit',
        json={'responses': responses},
        token=session['access_token']
    )

    if status_code == 200:
        flash('Test submitted successfully!', 'success')
        return redirect(url_for('home'))
    else:
        flash(response_data.get('error', {}).get('message', 'There was an error submitting your test.'), 'danger')
        return redirect(url_for('dashboard'))

@app.route('/test/start-self-assessment/<test_id>')
def start_self_assessment(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))

    start_data, start_status = make_api_request(
        'POST', f'tests/{test_id}/start-self-assessment', token=session['access_token']
    )
    if start_status != 201:
        flash('Could not start the test.', 'danger')
        return redirect(url_for('dashboard'))
    
    attempt_id = start_data.get('data', {}).get('id')

    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    if test_status != 200:
        flash('Could not fetch the test.', 'danger')
        return redirect(url_for('dashboard'))

    test = test_data
    return render_template('take_test.html', test=test, attempt_id=attempt_id)

# Add these new routes to your existing app.py

@app.route('/recruiter/tests')
def recruiter_tests():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    user_data, status_code = make_api_request('GET', 'auth/me', token=session['access_token'])
    
    if status_code != 200 or user_data.get('user_type', {}).get('name') != 'recruiter':
        flash('Access denied. Recruiter privileges required.', 'danger')
        return redirect(url_for('dashboard'))
    
    tests_data, tests_status = make_api_request('GET', 'tests', token=session['access_token'])
    tests = tests_data.get('data', []) if tests_status == 200 else []
    
    return render_template('recruiter_tests.html', tests=tests)

@app.route('/tests/create', methods=['GET', 'POST'])
def create_test():
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        data = {
            'title': request.form.get('title'),
            'description': request.form.get('description'),
            'time_limit_minutes': request.form.get('time_limit_minutes'),
            'pass_threshold': request.form.get('pass_threshold'),
            'is_public': 1 if request.form.get('is_public') else 0,
            'show_answers': 1 if request.form.get('show_answers') else 0,
            'randomize_questions': 1 if request.form.get('randomize_questions') else 0,
            'is_active': 1
        }
        
        response_data, status_code = make_api_request('POST', 'tests', json=data, token=session['access_token'])
        
        if status_code == 201:
            flash('Test created successfully!', 'success')
            return redirect(url_for('recruiter_tests'))
        else:
            flash(response_data.get('error', {}).get('message', 'Error creating test.'), 'danger')
    
    return render_template('create_test.html')

@app.route('/tests/<int:test_id>/edit', methods=['GET', 'POST'])
def edit_test(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        data = {
            'title': request.form.get('title'),
            'description': request.form.get('description'),
            'time_limit_minutes': request.form.get('time_limit_minutes'),
            'pass_threshold': request.form.get('pass_threshold'),
            'is_public': 1 if request.form.get('is_public') else 0,
            'show_answers': 1 if request.form.get('show_answers') else 0,
            'randomize_questions': 1 if request.form.get('randomize_questions') else 0,
            'is_active': 1 if request.form.get('is_active') else 0
        }
        
        response_data, status_code = make_api_request('PUT', f'tests/{test_id}', json=data, token=session['access_token'])
        
        if status_code == 200:
            flash('Test updated successfully!', 'success')
            return redirect(url_for('test_details', test_id=test_id))
        else:
            flash(response_data.get('error', {}).get('message', 'Error updating test.'), 'danger')
    
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    return render_template('edit_test.html', test=test_data.get('data', {}))

@app.route('/tests/<int:test_id>')
def test_details(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    test_data, test_status = make_api_request('GET', f'tests/{test_id}', token=session['access_token'])
    
    if test_status != 200:
        flash('Test not found.', 'danger')
        return redirect(url_for('recruiter_tests'))
    
    return render_template('test_details.html', test=test_data.get('data', {}))

@app.route('/tests/<int:test_id>/delete', methods=['POST'])
def delete_test(test_id):
    if 'access_token' not in session:
        return redirect(url_for('login'))
    
    response_data, status_code = make_api_request('DELETE', f'tests/{test_id}', token=session['access_token'])
    
    if status_code == 204:
        flash('Test deleted successfully.', 'success')
    else:
        flash(response_data.get('error', {}).get('message', 'Error deleting test.'), 'danger')
    
    return redirect(url_for('recruiter_tests'))
    
if __name__ == '__main__':
    app.run(debug=True)
