<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:p="http://www.openarchives.org/OAI/2.0/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" version='1.0'> 

<xsl:template match='p:OAI-PMH'>
<root xmlns:dc="http://purl.org/dc/elements/1.1/">
  <xsl:for-each select='//p:record'>
      <record>
        
        <dc:identifier>
          <xsl:apply-templates select="//p:identifier" />             
        </dc:identifier>
        <dc:identifier>
          <xsl:apply-templates select="//dc:identifier" />
        </dc:identifier>
        <dc:date type="datestamp">
          <xsl:apply-templates select="//p:datestamp" />            
        </dc:date>                  
        <dc:title>
          <xsl:apply-templates select="//dc:title" />
        </dc:title>
        <dc:creator>
          <xsl:apply-templates select="//dc:creator" />
        </dc:creator>
        <dc:subject>
          <xsl:apply-templates select="//dc:subject" />
        </dc:subject>
        <dc:description>
          <xsl:apply-templates select="//dc:description" />
        </dc:description>
        <dc:date type="created">
          <xsl:apply-templates select="//dc:date" />
        </dc:date>
        <dc:type>
          <xsl:apply-templates select="//dc:type" />
        </dc:type>
        <dc:language>
          <xsl:apply-templates select="//dc:language" />
        </dc:language>          
              
      </record>
    </xsl:for-each>
</root>
</xsl:template>

  <xsl:template match="node()">
    <xsl:value-of select="." />
  </xsl:template>
  

</xsl:stylesheet>