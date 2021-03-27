create table EmailLog
(
	id int identity,
	srcIP varchar(39) not null,
	srcHostname varchar(50) not null,
	srcHelo varchar(50) not null,
	srcPort int not null,
	srcProto varchar(10) not null,
	size int not null,
	mailServer varchar(10) not null,
	postfixID varchar(50) not null,
	postfixRecipients varchar(max) not null,
	[to] varchar(max) not null,
	[from] varchar(max) not null,
	CC varchar(max) not null,
	BCC varchar(max) not null,
	sendDate datetime not null,
	subject varchar(max) not null,
	contentHash varchar(32) not null
)