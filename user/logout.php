<?php
include('../includes/config.php');
include('../includes/functions.php');

revoke_session_token();
clear_auth_marker();
$_SESSION = [];
session_regenerate_id(true);
flash('success', 'You have been signed out.');
redirect('user/login.php');
