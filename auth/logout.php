<?php
session_start();
session_unset();
session_destroy();
header('Location: /medi/?logout=1');
exit;


