<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:p="http://www.openarchives.org/OAI/2.0/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcc="http://purl.org/dc/elements/1.1/"  version='2.0' exclude-result-prefixes="p oai_dc dcc"> 
<xsl:output method="xml" indent="yes" encoding="utf-8" />

  <xsl:template match='p:OAI-PMH'>
  <rdf:RDF>
    <xsl:apply-templates select="p:ListRecords" />    
  </rdf:RDF>
  </xsl:template>
  
  <xsl:template match="p:ListRecords">
    <xsl:apply-templates select="p:record" />
  </xsl:template>
  
  
  <xsl:template match="p:record">    
    <xsl:apply-templates select="p:header" />    
    <xsl:apply-templates select="p:metadata/oai_dc:dc" />
  </xsl:template>
  
  <xsl:template match="p:header">    
    <rdf:Description rdf:about="{p:identifier}">
      <dcterms:identifier>
        <xsl:value-of select="p:identifier" />
      </dcterms:identifier>
      <dcterms:datetime rdf:datatype="http://www.w3.org/2001/XMLSchema#datetime">
        <xsl:value-of select="p:datestamp" />            
      </dcterms:datetime>
      <dcterms:date rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:variable name="datum">
          <xsl:value-of select="p:datestamp" />
        </xsl:variable>
        <xsl:value-of select="substring-before($datum,'T')"/>
        
      </dcterms:date>
    </rdf:Description>
  </xsl:template>
  
  <xsl:template match="p:metadata/oai_dc:dc">
    <rdf:Description rdf:about="{dcc:identifier}">
      <dcterms:identifier>
        <xsl:value-of select="dcc:identifier" />
      </dcterms:identifier>
      <dcterms:title>
        <xsl:value-of select="dcc:title" />
      </dcterms:title>
      <dcterms:creator>
        <xsl:value-of select="dcc:creator" />
      </dcterms:creator>
      
      <xsl:apply-templates select="dcc:subject" />
      
      <dcterms:description>
        <xsl:value-of select="dcc:description" />
      </dcterms:description>
      <dcterms:created rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:value-of select="dcc:date" />
      </dcterms:created>
      <dcterms:type>
        <xsl:value-of select="dcc:type" />
      </dcterms:type>
      <dcterms:language>
        <xsl:value-of select="dcc:language" />
      </dcterms:language>
    </rdf:Description> 
  </xsl:template>
    
 <xsl:template match="dcc:subject">
   <xsl:variable name="subject">
     <xsl:value-of select="." />
   </xsl:variable>
   
   <xsl:for-each select="tokenize($subject, ';')">
     <dcterms:subject>
       <xsl:value-of select="." />
     </dcterms:subject>
   </xsl:for-each>
   
 </xsl:template>


</xsl:stylesheet>