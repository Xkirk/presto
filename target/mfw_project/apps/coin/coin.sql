/* 金币流水 */
CREATE TABLE coin_record (
  a_id int NOT NULL AUTO_INCREMENT,                     -- 日志id
  uid int NOT NULL DEFAULT 0,                           -- 用户uid
  type int NOT NULL DEFAULT 0,                          -- 渠道
  value int not null default 0,                         -- 金币数 正负之分
  refer_id bigint NOT NULL DEFAULT 0,
  c_type int not null default 0,                          -- 操作的类型

--   used_status tinyint not null default 0,               -- 0/1/2 未使用/部分使用/全部使用
--   used_coin int not null default 0,                    -- 已使用金币数
--   rids varchar(512) not null default '',                -- 关联使用金币的id {id : coin}

  time timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (a_id),
  KEY(uid,type,time),
  KEY(uid,time),
  KEY(type,time),
  KEY(time),
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;



/* 金币发放渠道 */
CREATE TABLE coin_send_channels (
  ch_id int NOT NULL ,                                      -- 渠道id
  title varchar(50) not null default '',                    -- 渠道名称
  send_desc varchar(50) not null default '',                -- 发放描述
  return_desc varchar(50) not null default '',              -- 退还描述
  content varchar(50) not null default '',                  -- 详细描述
  c_type tinyint NOT NULL DEFAULT 0,                        -- 发放/回收/退还/转账转出/转账转入 1/2/3/4/5
  status tinyint NOT NULL DEFAULT 0,                        -- 状态 1/2 正常/关闭
  total_coin int not null default 0,                        -- 渠道发放的总金币数  0代表无限制
  single_max_coin int not null default 0,                   -- 单次最多发送的金币数 0代表无限制（浮动）
  sended_coin int not null default 0,                       -- 已发送金币数
  return_coin int not null default 0,                       -- 退还金币数
  expire_date date not null default '0000-00-00',           -- 发放截止时间
  last_send_time timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',  -- 最后发放时间
  create_admin_uid int not null default 0,                  -- 创建的管理员id
  ctime timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',   -- 创建时间
  modify_admin_uid int not null default 0,                  -- 最后修改的管理员id
  mtime timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',   -- 修改时间
  PRIMARY KEY (ch_id),
  UNIQUE KEY(title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE coin_user_years_data (
  uid int NOT NULL DEFAULT 0,
  year int NOT NULL DEFAULT 0,
  get_coin int NOT NULL DEFAULT 0,
  used_coin int NOT NULL DEFAULT 0,
  expire_coin int NOT NULL DEFAULT 0,
  PRIMARY KEY (uid,year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8