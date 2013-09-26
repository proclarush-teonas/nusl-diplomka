<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="urn:TEST:test" exclude-result-prefixes="s">
	<xsl:output method="text" encoding="utf-8"/>
	<xsl:template match="/">
		<xsl:message terminate="no">This is an XSLT message with terminate set to <a>"no"</a>.</xsl:message>
		<xsl:message terminate="yes">This is an XSLT message with terminate set to <s:a>"yes"</s:a>.</xsl:message>
	</xsl:template>
</xsl:stylesheet>