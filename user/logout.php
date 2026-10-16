<?php
include('../includes/functions.php');

$_SESSION = [];
session_regenerate_id(true);
flash('success', 'You have been signed out.');
redirect('user/login.php');
