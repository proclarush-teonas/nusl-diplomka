<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" encoding="utf-8" indent="no"/>
	<xsl:strip-space elements="root" />
	<xsl:template match="/">
		<html>
			<head>
				<title>Untitled Document</title>
			</head>
			<body xml:space="preserve"> <xsl:apply-templates/> </body>
		</html>
	</xsl:template>
</xsl:stylesheet>