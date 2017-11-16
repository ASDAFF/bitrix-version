create table b_mail_mailbox
(
   ID int(18) not null auto_increment,
   TIMESTAMP_X timestamp,
   LID char(2) not null,
   ACTIVE char(1) not null default 'Y',
   NAME varchar(255),
   SERVER varchar(255) not null,
   PORT int(18) not null default '110',
   LOGIN varchar(255),
   CHARSET varchar(255),
   `PASSWORD` varchar(255),
   DESCRIPTION text,
   USE_MD5 char(1) not null default 'N',
   DELETE_MESSAGES char(1) not null default 'N',
   PERIOD_CHECK int(15),
   MAX_MSG_COUNT int(11) default '0',
   MAX_MSG_SIZE int(11) default '0',
   MAX_KEEP_DAYS int(11) default '0',
   USE_TLS char(1) not null default 'N',
   SERVER_TYPE varchar(5) NOT NULL DEFAULT 'pop3',
   DOMAINS varchar(255) null,
   RELAY char(1) NOT NULL DEFAULT 'Y',
   AUTH_RELAY char(1) NOT NULL DEFAULT 'Y',
   primary key (ID)
);


create table b_mail_filter
(
   ID int(18) not null auto_increment,
   TIMESTAMP_X timestamp,
   MAILBOX_ID int(18) not null,
   PARENT_FILTER_ID int(18),
   NAME varchar(255),
   DESCRIPTION text,
   SORT int(18) not null default '500',
   ACTIVE char(1) not null default 'Y',
   PHP_CONDITION text,
   WHEN_MAIL_RECEIVED char(1) not null default 'N',
   WHEN_MANUALLY_RUN char(1) not null default 'N',
   SPAM_RATING decimal(9,4),
   SPAM_RATING_TYPE char(1) default '<',
   MESSAGE_SIZE int(18),
   MESSAGE_SIZE_TYPE char(1) default '<',
   MESSAGE_SIZE_UNIT char(1),
   ACTION_STOP_EXEC char(1) not null default 'N',
   ACTION_DELETE_MESSAGE char(1) not null default 'N',
   ACTION_READ char(1) not null default '-',
   ACTION_PHP text,
   ACTION_TYPE varchar(50),
   ACTION_VARS text,
   ACTION_SPAM char(1) not null default '-',
   primary key (ID),
   index IX_MAIL_FILTER_MAILBOX (MAILBOX_ID)
);


create table b_mail_filter_cond
(
   ID int(11) not null auto_increment,
   FILTER_ID int(11) not null,
   `TYPE` varchar(50) not null,
   STRINGS text not null,
   COMPARE_TYPE varchar(30) not null default 'CONTAIN',
   primary key (ID)
);


create table b_mail_message
(
   ID int(18) not null auto_increment,
   MAILBOX_ID int(18) not null,
   DATE_INSERT datetime not null,
   FULL_TEXT longtext,
   MESSAGE_SIZE int(18) not null,
   HEADER text,
   FIELD_DATE datetime,
   FIELD_FROM varchar(255),
   FIELD_REPLY_TO varchar(255),
   FIELD_TO varchar(255),
   FIELD_CC varchar(255),
   FIELD_BCC varchar(255),
   FIELD_PRIORITY int(18) not null default '3',
   SUBJECT varchar(255),
   BODY longtext,
   ATTACHMENTS int(18) default '0',
   NEW_MESSAGE char(1) default 'Y',
   SPAM char(1) not null default '?',
   SPAM_RATING decimal(18,4),
   SPAM_WORDS varchar(255),
   SPAM_LAST_RESULT char(1) not null default 'N',
   FOR_SPAM_TEST mediumtext,
   EXTERNAL_ID varchar(255),
   MSG_ID varchar(255) NULL,
   IN_REPLY_TO varchar(255) NULL,
   primary key (ID),
   index IX_MAIL_MESSAGE (MAILBOX_ID)
);


create table b_mail_message_uid
(
   ID varchar(32) not null,
   MAILBOX_ID int(18) not null,
   SESSION_ID varchar(32) not null,
   TIMESTAMP_X timestamp,
   DATE_INSERT datetime not null,
   MESSAGE_ID int(18) not null,
   primary key (ID, MAILBOX_ID),
   index IX_MAIL_MSG_UID (MAILBOX_ID)
);

create table b_mail_msg_attachment
(
   ID int(18) not null auto_increment,
   MESSAGE_ID int(18) not null,
   FILE_ID int(18) not null default '0',
   FILE_NAME varchar(255),
   FILE_SIZE int(11) not null default '0',
   FILE_DATA longblob,
   CONTENT_TYPE varchar(255),
   IMAGE_WIDTH int(18),
   IMAGE_HEIGHT int(18),
   primary key (ID),
   index IX_MAIL_MESSATTACHMENT (MESSAGE_ID)
);

create table b_mail_spam_weight
(
   WORD_ID varchar(32) not null,
   WORD_REAL varchar(50) not null,
   GOOD_CNT int(18) not null default '0',
   BAD_CNT int(18) not null default '0',
   TOTAL_CNT int(18) not null default '0',
   TIMESTAMP_X timestamp,
   primary key (WORD_ID)
);

create table b_mail_log
(
   ID int(18) not null auto_increment,
   MAILBOX_ID int(18) not null default '0',
   FILTER_ID int(18),
   MESSAGE_ID int(18),
   LOG_TYPE varchar(50),
   DATE_INSERT datetime not null,
   STATUS_GOOD char(1) not null default 'Y',
   MESSAGE varchar(255) null,
   primary key (ID),
   index IX_MAIL_MSGLOG_1 (MAILBOX_ID),
   index IX_MAIL_MSGLOG_2 (MESSAGE_ID)
);


CREATE INDEX mail_spam_good ON b_mail_spam_weight(GOOD_CNT);

CREATE INDEX mail_spam_bad ON b_mail_spam_weight(BAD_CNT);

