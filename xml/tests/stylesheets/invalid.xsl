<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" encoding="utf-8" indent="no"/>
	<xsl:param name="test" select="'default'"/>
	<xsl:param name="path" select="'unchanged'"/>
	<xsl:template match="/">
		<html>
			<head>
				<title>Untitled Document</title>
			</head>
			<body>
				<xsl:apply-templates/>
			</body>
		</html>
		<xsl:template/>
	</xsl:template>
</xsl:stylesheet>