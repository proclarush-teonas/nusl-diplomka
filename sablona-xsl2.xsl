<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:p="http://www.openarchives.org/OAI/2.0/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/terms/" xmlns:dcc="http://purl.org/dc/elements/1.1/"  version='2.0' exclude-result-prefixes="p oai_dc dcc"> 
<xsl:output method="xml" indent="yes" />

  <xsl:template match='p:OAI-PMH'>
  <rdf:RDF>
    <xsl:apply-templates select="/*:OAI-PMH/*:ListRecords/p:record" />    
  </rdf:RDF>
  </xsl:template>
  
  
  <xsl:template match="p:record">
    <rdf:Description rdf:about="{/*:OAI-PMH/*:ListRecords/*:record/*:header/p:identifier}">
      <dc:datetime rdf:datatype="http://www.w3.org/2001/XMLSchema#datetime">
        <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:header/p:datestamp" />            
      </dc:datetime>
      <dc:date rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:variable name="datum">
          <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:header/p:datestamp" />
        </xsl:variable>
        <xsl:value-of select="substring-before($datum,'T')"/>
                    
      </dc:date>
    </rdf:Description>
            
    <rdf:Description rdf:about="{/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:identifier}">
      <dc:identifier>
        <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:header/p:identifier" />
      </dc:identifier>
      <dc:title>
        <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:title" />
        </dc:title>
        <dc:creator>
          <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:creator" />
        </dc:creator>
        <dc:subject>
          <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:subject" />
        </dc:subject>
        <dc:description>
          <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:description" />
        </dc:description>
      <dc:created rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:date" />
        </dc:created>
        <dc:type>
          <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:type" />
        </dc:type>
        <dc:language>
          <xsl:value-of select="/*:OAI-PMH/*:ListRecords/*:record/*:metadata/*:dc/dcc:language" />
        </dc:language>      
    </rdf:Description>  
</xsl:template>
  

</xsl:stylesheet>