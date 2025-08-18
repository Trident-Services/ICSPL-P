<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logged Out</title>
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: #0f172a; /* dark blue/black */
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
      text-align: center;
      overflow: hidden;
    }
    .thankyou {
      animation: fadeIn 1.2s ease-in-out;
    }
    h1 {
      font-size: 2.5rem;
      margin-bottom: 10px;
      color: #4ade80; /* green */
    }
    p {
      font-size: 1.2rem;
      color: #e2e8f0;
    }

    /* Checkmark Animation */
    .checkmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: inline-block;
      stroke-width: 4;
      stroke: #fff;
      stroke-miterlimit: 10;
      margin: 20px auto;
      box-shadow: 0 0 20px #22c55e;
      animation: scale .5s ease-in-out;
    }
    .checkmark__circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 4;
      stroke-miterlimit: 10;
      stroke: #22c55e;
      fill: none;
      animation: stroke 0.6s cubic-bezier(.65, .05, .36, 1) forwards;
    }
    .checkmark__check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      stroke: #22c55e;
      animation: stroke 0.4s cubic-bezier(.65, .05, .36, 1) 0.7s forwards;
    }
    @keyframes stroke {
      100% { stroke-dashoffset: 0; }
    }
    @keyframes scale {
      0%, 100% { transform: none; }
      50% { transform: scale3d(1.1, 1.1, 1); }
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
  </style>
</head>
<body>
  <div class="thankyou">
    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
      <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
      <path class="checkmark__check" fill="none" d="M14 27l7 7 16-16"/>
    </svg>
    <h1>Thank You!</h1>
    <p>You have been logged out successfully.</p>
    <p>Redirecting to login...</p>
  </div>

  <script>
    // Redirect after 3 seconds
    setTimeout(() => {
      window.location.href = "/login";
    }, 3000);
  </script>
</body>
</html>
