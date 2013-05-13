<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:p="http://www.openarchives.org/OAI/2.0/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" version='1.0'> 
<xsl:output method="xml" indent="yes" />
<xsl:template match='p:OAI-PMH'>
  
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">

    
  <xsl:for-each select='//p:record'>
    <rdf:Description rdf:about="{//p:identifier}">
      <datestamp>
        <xsl:apply-templates select="//p:datestamp" />            
      </datestamp>
      <dc:date rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:variable name="datum">
          <xsl:apply-templates select="//p:datestamp" />
        </xsl:variable>
        <xsl:value-of select="substring-before($datum,'T')"/>
                    
      </dc:date>
    </rdf:Description>
            
    <rdf:Description rdf:about="{//dc:identifier}">
      <dc:identifier>
        <xsl:apply-templates select="//p:identifier" />
      </dc:identifier>
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
      <dc:date rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
          <xsl:apply-templates select="//dc:date" />
        </dc:date>
        <dc:type>
          <xsl:apply-templates select="//dc:type" />
        </dc:type>
        <dc:language>
          <xsl:apply-templates select="//dc:language" />
        </dc:language>      
    </rdf:Description>
    </xsl:for-each>
  
</rdf:RDF>
</xsl:template>

  <xsl:template match="node()">
    <xsl:value-of select="." />
  </xsl:template>
  

</xsl:stylesheet>