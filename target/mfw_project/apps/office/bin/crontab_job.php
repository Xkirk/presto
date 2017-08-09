<?php

namespace apps\office;

return array (
	array('cmd' => 'php delivery_notice.php', 'hour' => '10'),
	array('cmd' => 'php calculate_annual_leave.php', 'day' => 1),   //每月1日执行计算年假任务
    array('cmd' => 'php calculate_careOff.php', 'day' => 1),        //每月1日执行计算关爱假任务
);
