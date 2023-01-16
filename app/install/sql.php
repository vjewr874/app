<?php
return [
    'CREATE TABLE IF NOT EXISTS `[db_prefix]admin` (
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL COMMENT \'用户名\',
  `password` varchar(245) NOT NULL COMMENT \'密码\',
  `name` varchar(20) NOT NULL COMMENT \'姓名\',
  `create_time` bigint(11) NOT NULL COMMENT \'创建时间\',
  `update_time` bigint(11) NOT NULL COMMENT \'修改时间\',
  `delete_time` bigint(11) DEFAULT NULL COMMENT \'删除时间\'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]app` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT \'应用名称\',
  `vn` int(11) NOT NULL DEFAULT \'1000\' COMMENT \'数字版本\',
  `author` varchar(20) DEFAULT NULL COMMENT \'作者名称\',
  `team_name` varchar(200) DEFAULT NULL COMMENT \'团队名称\',
  `qq` varchar(11) DEFAULT NULL COMMENT \'客服QQ\',
  `url` varchar(100) DEFAULT NULL COMMENT \'官方地址\',
  `auth_verify_reg` varchar(100) DEFAULT NULL COMMENT \'授权关键正则验证,空则不验证\',
  `auth_file_name` varchar(100) NOT NULL COMMENT \'授权文件\',
  `auth_file_coding` varchar(20) NOT NULL DEFAULT \'utf-8\' COMMENT \'文件编码\',
  `auth_file_content` text NOT NULL COMMENT \'授权文件内容\',
  `create_time` bigint(11) NOT NULL COMMENT \'创建时间\',
  `update_time` bigint(11) NOT NULL COMMENT \'更新时间\',
  `delete_time` bigint(11) DEFAULT NULL COMMENT \'删除时间\',
  `status` int(1) NOT NULL DEFAULT \'1\' COMMENT \'状态\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]auth` (
  `id` int(11) NOT NULL,
  `appid` int(11) NOT NULL COMMENT \'应用ID\',
  `auth_id` bigint(15) NOT NULL COMMENT \'授权ID\',
  `auth_key` varchar(32) NOT NULL COMMENT \'授权秘钥\',
  `auth_content` varchar(32) DEFAULT NULL COMMENT \'授权内容\',
  `qq` bigint(10) NOT NULL COMMENT \'授权QQ\',
  `create_name` varchar(50) NOT NULL COMMENT \'创建用户名\',
  `create_user` int(11) DEFAULT NULL COMMENT \'创建用户\',
  `create_time` bigint(11) DEFAULT \'0\' COMMENT \'创建时间\',
  `expire_time` bigint(11) DEFAULT NULL COMMENT \'到期时间\',
  `delete_time` bigint(11) DEFAULT NULL COMMENT \'删除时间\',
  `status` int(1) NOT NULL DEFAULT \'1\' COMMENT \'状态\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]auth_log` (
  `id` int(11) NOT NULL,
  `type` int(1) NOT NULL DEFAULT \'0\' COMMENT \'类型\',
  `name` varchar(32) NOT NULL DEFAULT \'无\' COMMENT \'日志名称\',
  `appid` int(11) NOT NULL DEFAULT \'0\' COMMENT \'应用ID\',
  `auth_id` bigint(15) NOT NULL DEFAULT \'0\' COMMENT \'授权ID\',
  `auth_content` varchar(50) DEFAULT NULL COMMENT \'授权内容\',
  `content` text COMMENT \'日志内容\',
  `ip` varchar(20) DEFAULT NULL COMMENT \'IP\',
  `create_time` bigint(11) NOT NULL COMMENT \'创建时间\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]card_password` (
  `id` int(11) NOT NULL,
  `appid` int(11) NOT NULL COMMENT \'应用ID\',
  `card_number` varchar(40) NOT NULL COMMENT \'卡号\',
  `day` int(11) NOT NULL DEFAULT \'0\' COMMENT \'面值天数\',
  `create_time` bigint(10) NOT NULL DEFAULT \'0\' COMMENT \'创建时间\',
  `usage_time` bigint(10) NOT NULL DEFAULT \'0\' COMMENT \'使用时间\',
  `expire_time` bigint(10) NOT NULL DEFAULT \'0\' COMMENT \'到期时间\',
  `create_user` int(11) DEFAULT NULL COMMENT \'创建用户ID\',
  `create_name` varchar(50) DEFAULT NULL COMMENT \'创建用户名\',
  `delete_time` bigint(11) DEFAULT NULL COMMENT \'删除时间\',
  `status` int(11) NOT NULL DEFAULT \'1\' COMMENT \'状态\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]config` (
  `name` varchar(255) NOT NULL COMMENT \'键\',
  `value` text NOT NULL COMMENT \'值\',
  `remark` varchar(100) DEFAULT NULL COMMENT \'备注\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    'INSERT INTO `auth_config` (`name`, `value`, `remark`) VALUES
(\'admin_domain\', \'\', \'后台域名\'),
(\'api_auto_encrypt\', \'1\', \'API自动识别加密\'),
(\'api_domain\', \'\', \'API域名\'),
(\'api_encrypt\', \'0\', \'API加密通信\'),
(\'api_log\', \'1\', \'API日志\'),
(\'icp_number\', \'湘ICP备18024031号-1\', \'网站备案号\'),
(\'index_domain\', \'\', \'首页域名\'),
(\'site_copyright\', \'Copyright © 2019. <a target=\"_blank\" href=\"https://www.ymkuzhan.com\">亿码酷站</a> All rights reserved.  \', \'网站版权信息\'),
(\'site_description\', \'xx多应用授权系统\', \'网站描述\'),
(\'site_index_title\', \'同样的产品超低的价格,优质的服务\', \'网站首页标题\'),
(\'site_keywords\', \'PHP授权系统,xx授权系统\', \'网站关键词\'),
(\'site_logo\', \'/logo.png\', \'网站LOGO\'),
(\'site_name\', \'亿码酷站\', \'网站名称\'),
(\'site_statis\', \'<script type=\"text/javascript\">var cnzz_protocol = ((\"https:\" == document.location.protocol) ? \" https://\" : \" http://\");document.write(unescape(\"%3Cspan id=\'\'cnzz_stat_icon_1258635040\'\'%3E%3C/span%3E%3Cscript src=\'\'\" + cnzz_protocol + \"s4.cnzz.com/z_stat.php%3Fid%3D1258635040%26show%3Dpic1\'\' type=\'\'text/javascript\'\'%3E%3C/script%3E\"));</script>\', \'站点统计代码\\r\\n\'),
(\'site_user_auth_number\', \'10\', \'用户中心首页显示授权条数\\r\\n\'),
(\'site_user_notice_number\', \'10\', \'用户中心首页显示公告数量\'),
(\'user_domain\', \'\', \'用户中心域名\');',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]log` (
  `id` int(11) NOT NULL,
  `type` varchar(15) NOT NULL COMMENT \'日志类型\',
  `user_id` int(11) DEFAULT NULL COMMENT \'用户ID\',
  `content` text NOT NULL COMMENT \'日志内容\',
  `create_time` bigint(11) NOT NULL COMMENT \'记录时间\',
  `ip` varchar(20) NOT NULL COMMENT \'操作人IP\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]notice` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL COMMENT \'创建者用户ID\',
  `is_top` int(11) NOT NULL DEFAULT \'0\' COMMENT \'顶置\',
  `title` varchar(255) NOT NULL COMMENT \'标题\',
  `content` text NOT NULL COMMENT \'内容\',
  `create_time` bigint(11) NOT NULL COMMENT \'创建时间\',
  `update_time` bigint(11) NOT NULL COMMENT \'修改时间\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]user` (
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL COMMENT \'用户名\',
  `password` varchar(245) NOT NULL COMMENT \'密码\',
  `name` varchar(20) NOT NULL COMMENT \'姓名\',
  `auth` varchar(255) DEFAULT NULL COMMENT \'授权\',
  `sid` int(11) DEFAULT NULL COMMENT \'上级ID\',
  `qq` varchar(11) DEFAULT NULL COMMENT \'QQ号\',
  `create_time` bigint(11) NOT NULL COMMENT \'创建时间\',
  `delete_time` bigint(11) DEFAULT NULL COMMENT \'删除时间\',
  `status` int(11) NOT NULL DEFAULT \'1\' COMMENT \'状态\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
CREATE TABLE IF NOT EXISTS `[db_prefix]version` (
  `id` int(11) NOT NULL,
  `appid` int(11) NOT NULL COMMENT \'应用ID\',
  `version` varchar(20) NOT NULL COMMENT \'版本号\',
  `content` text NOT NULL COMMENT \'更新内容\',
  `update_file` varchar(200) NOT NULL COMMENT \'更新包文件地址\',
  `complete_file` varchar(200) NOT NULL COMMENT \'完整包文件地址\',
  `release_time` int(11) NOT NULL COMMENT \'发布时间\',
  `delete_time` bigint(11) DEFAULT NULL COMMENT \'删除时间\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    '
ALTER TABLE `[db_prefix]admin`
  ADD PRIMARY KEY (`id`);',
    '
ALTER TABLE `[db_prefix]app`
  ADD PRIMARY KEY (`id`);',
    '
ALTER TABLE `[db_prefix]auth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qq` (`qq`),
  ADD KEY `auth_id` (`auth_id`),
  ADD KEY `auth_content` (`auth_content`),
  ADD KEY `appid_createUser_deleteTime_status` (`appid`,`create_user`,`delete_time`,`status`) USING BTREE,
  ADD KEY `id_appid_createUser_deleteTime` (`id`,`appid`,`create_user`,`delete_time`,`expire_time`) USING BTREE,
  ADD KEY `appid_createUser_expireTime_status` (`appid`,`create_user`,`expire_time`,`delete_time`,`status`) USING BTREE;',
    '
ALTER TABLE `[db_prefix]auth_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `type_authId_authContent` (`type`,`auth_id`,`auth_content`) USING BTREE;',
    '
ALTER TABLE `[db_prefix]card_password`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appid` (`appid`);',
    '
ALTER TABLE `[db_prefix]config`
  ADD PRIMARY KEY (`name`);',
    '
ALTER TABLE `[db_prefix]log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `type_userId` (`type`,`user_id`) USING BTREE;',
    '
ALTER TABLE `[db_prefix]notice`
  ADD PRIMARY KEY (`id`);',
    '
ALTER TABLE `[db_prefix]user`
  ADD PRIMARY KEY (`id`);',
    '
ALTER TABLE `[db_prefix]version`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appid` (`appid`);',
    '
ALTER TABLE `[db_prefix]admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;',
    '
ALTER TABLE `[db_prefix]app`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]auth_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]card_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',
    '
ALTER TABLE `[db_prefix]version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;',

];