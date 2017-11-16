create table if not exists b_idea_email_subscribe
(
    ID varchar(25) NOT NULL, 
    USER_ID int(18) NOT NULL,
    INDEX ix_idea_email_subscribe (ID, USER_ID)
);