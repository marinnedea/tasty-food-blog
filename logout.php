<?php
require 'includes/auth.php';
logout_user();
header('Location: /');
exit;
