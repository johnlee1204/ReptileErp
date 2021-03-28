-- INSERT INTO FWE_DEV
CREATE TABLE [dbo].[inventoryLog](
	[dateTime] [datetime] NOT NULL,
	[ip] [varchar](15) NOT NULL,
	[class] [varchar](40) NOT NULL,
	[method] [varchar](40) NOT NULL,
	[part] [char](25) NULL,
	[partrev] [char](3) NULL,
	[qty] [int] NULL,
	[fromLocation] [varchar](14) NULL,
	[fromBin] [varchar](14) NULL,
	[toLocation] [varchar](14) NULL,
	[toBin] [varchar](14) NULL
) ON [PRIMARY]