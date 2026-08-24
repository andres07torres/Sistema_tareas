<?php require "config/database.php"; $db = (new Database())->getConnection(); $stmt = $db->query("SELECT * FROM tareas ORDER BY id DESC LIMIT 5"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); ?>
