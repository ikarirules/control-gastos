<?php
// Redirige la raíz del proyecto al frontend
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
header('Location: ' . $base . '/frontend/web/', true, 302);
exit;
