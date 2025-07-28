<?php
session_start();
session_unset();
session_destroy();
header("Location: /login"); // redirect back to login
exit();
