# Student Expo App

This Expo app is built for students to log in using existing Laravel backend accounts.

## Setup

1. Install dependencies from the `student-app` folder:

    ```bash
    cd student-app
    npm install
    ```

2. If needed, update the API backend URL in `src/api/api.js`.
   The app now auto-detects the local development host for Expo/devices,
   but you can still override it with `process.env.API_BASE_URL`.

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
