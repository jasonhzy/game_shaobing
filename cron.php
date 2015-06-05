<?php
        $database = 'games_shaobing';

        $conn = new mysqli('localhost', 'root', '', $database);

        $old = umask(0);
        $dir = date('Y-m-d');
        $path = '/var/www/files/';

        if (!is_dir($path.$dir)) {
                mkdir($path.$dir, 0777, true);
                umask($old);
        }
        $filepath = $path.$dir.'/';
        $filename = $filepath.$dir.'.csv';

        $conn->query("SELECT username, IF(userphone = 'none', '', userphone) AS userphone, score,joindate, IF(is_exchange = '1', '已兑换', '未兑换') AS is_exchange,  IFNULL(`sn_code`, ''), IFNULL(`gift_name`, '') 
					       INTO OUTFILE '$filename'  FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
					FROM tp_user u 
					INNER JOIN tp_score s ON u.id = s.uid 
					LEFT JOIN `tp_lottery` ON `tp_lottery`.`uid` = u.`id`
					LEFT JOIN `tp_gift` ON `tp_gift`.`id` = `tp_lottery`.`gid`
					ORDER BY score DESC, joindate DESC");
        exec("mysqldump -uroot $database  > $filepath$dir.sql");
        if (file_exists($filename)) {
                $conn->query("TRUNCATE TABLE `tp_score`");
        }
        mysqli_close($conn);