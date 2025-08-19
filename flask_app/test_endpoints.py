import requests

# The base URL of your running Flask application
FLASK_APP_URL = 'http://127.0.0.1:5000'

def test_register():
    """Tests the /register endpoint."""
    print("--- Testing Registration ---")
    register_url = f"{FLASK_APP_URL}/register"
    
    # --- Test Case 1: Valid Registration Data ---
    print("\n[TC1: Valid Data]")
    form_data = {
        'name': 'Test User',
        'email': 'testuser@example.com',
        'password': 'password123',
        'password_confirmation': 'password123',
        'user_type_id': '1'  # Candidate
    }
    
    try:
        # We send the data as `data` to simulate a form post, which is what the browser does.
        # The Flask app should receive this in `request.form`.
        response = requests.post(register_url, data=form_data, allow_redirects=True, timeout=15)
        
        print(f"Status Code: {response.status_code}")
        print(f"Final URL: {response.url}")
        if "Login successful" in response.text or "Registration successful" in response.text:
            print("Result: SUCCESS - Registration appears to have worked and redirected.")
        elif "Invalid credentials" in response.text:
            print("Result: FAIL - Received 'Invalid credentials'.")
        elif "failed" in response.text:
             print("Result: FAIL - Registration failed. Check flashed messages.")
        else:
            print("Result: UNKNOWN - Check the response content below.")
            
        # Uncomment the line below to see the full HTML response
        # print("Response Content:\n", response.text)

    except requests.exceptions.RequestException as e:
        print(f"Result: ERROR - Could not connect to the Flask app: {e}")

def test_login():
    """Tests the /login endpoint."""
    print("\n--- Testing Login ---")
    login_url = f"{FLASK_APP_URL}/login"
    
    # --- Test Case 1: Valid Login Data ---
    print("\n[TC1: Valid Credentials]")
    form_data = {
        'email': 'testuser@example.com',
        'password': 'password123'
    }
    
    try:
        response = requests.post(login_url, data=form_data, allow_redirects=True, timeout=15)
        
        print(f"Status Code: {response.status_code}")
        print(f"Final URL: {response.url}")
        if "dashboard" in response.url:
            print("Result: SUCCESS - Login appears to have worked and redirected to the dashboard.")
        elif "Invalid credentials" in response.text:
            print("Result: FAIL - Received 'Invalid credentials'.")
        else:
            print("Result: UNKNOWN - Check the response content below.")
            
        # Uncomment the line below to see the full HTML response
        # print("Response Content:\n", response.text)

    except requests.exceptions.RequestException as e:
        print(f"Result: ERROR - Could not connect to the Flask app: {e}")


if __name__ == '__main__':
    print("Starting endpoint tests for the Flask application...")
    print("Please ensure your Flask app is running with the latest changes.")
    
    # Note: We test registration first to ensure the user exists for the login test.
    # In a real test suite, you'd use a clean database for each test.
    test_register()
    test_login()
    
    print("\n--- Tests Finished ---")
