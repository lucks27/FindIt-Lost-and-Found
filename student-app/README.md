# Student Expo App

This Expo app is built for students to log in using existing Laravel backend accounts.

## Setup

1. Install dependencies from the `student-app` folder:

    ```bash
    cd student-app
    npm install
    ```

2. The app auto-detects the Expo development host for phones on the same Wi-Fi.
    To use a specific backend URL, create `student-app/.env` with:

     ```text
     EXPO_PUBLIC_API_BASE_URL=http://YOUR_COMPUTER_IP:8000
     ```

    The computer and phone must be on the same Wi-Fi, and Laravel must listen on
    `0.0.0.0` rather than only `127.0.0.1`.

3. Run the app:
    ```bash
    npm start
    ```

## Authentication

- The app uses Axios to call Laravel Sanctum endpoints.
- Login sends credentials securely and optionally preserves an API token if the backend returns one.
- User details are retrieved from `/api/user` and shown on the home screen.

## Notes

- No registration screen is included: students log in using existing Laravel accounts.
- Credentials are handled only during login and are not stored in plain text.
- The app supports session restoration by checking a saved token and by calling the backend on startup.
