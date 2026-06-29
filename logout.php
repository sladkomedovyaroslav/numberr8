<?php
session_start();
session_destroy();
header('Location: /restaurant/');
exit;