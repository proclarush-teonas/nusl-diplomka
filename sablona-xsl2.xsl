<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:p="http://www.openarchives.org/OAI/2.0/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcc="http://purl.org/dc/elements/1.1/" xmlns:biro="http://purl.org/spar/biro/" xmlns:ld="http://ld.opendata.cz/resource/nusl-biro/" xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"  version='2.0' exclude-result-prefixes="p oai_dc dcc"> 
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
    <xsl:variable name="myrec">ld:record#</xsl:variable>
    <xsl:variable name="myref">ld:reference#</xsl:variable>
    <xsl:variable name="ajdy">
      <xsl:value-of select="substring-after(substring-after(p:identifier, ':'), ':')" />
    </xsl:variable>
    <biro:BibliographicRecord rdf:about="{$myrec}{$ajdy}">
      <dcterms:identifier>
        <xsl:value-of select="$myref" />
        <xsl:value-of select="$ajdy" />
      </dcterms:identifier>
      <dcterms:created rdf:datatype="http://www.w3.org/TR/xmlschema-2/#dateTime">
        <xsl:value-of select="p:datestamp" />            
      </dcterms:created>
      <dcterms:date rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:variable name="datum">
          <xsl:value-of select="p:datestamp" />
        </xsl:variable>
        <xsl:value-of select="substring-before($datum,'T')"/>
        
      </dcterms:date>
    </biro:BibliographicRecord>
  </xsl:template>
  
  <xsl:template match="p:metadata/oai_dc:dc">
    <xsl:variable name="ajdy">
      <xsl:value-of select="substring-after(dcc:identifier, '-')" />
    </xsl:variable>
    <xsl:variable name="myref">ld:reference#</xsl:variable>
    <xsl:variable name="mydoc">ld:document#</xsl:variable>
    <!-- jake pouzit uri, kdyz to ma byt odkaz na praci -->
    <biro:BibliographicReference rdf:about="{$myref}{$ajdy}">
      <dcterms:identifier>
        <xsl:value-of select="$mydoc" />
        <xsl:value-of select="$ajdy" />  
      </dcterms:identifier>
      <rdfs:seeAlso>
        <xsl:value-of select="dcc:identifier" />
      </rdfs:seeAlso>
      
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
        <!-- nejaky lepsi typ, s prolinkovanim -->
        <xsl:value-of select="dcc:type" />
      </dcterms:type>
      <dcterms:language>
        <xsl:value-of select="dcc:language" />
      </dcterms:language>
    </biro:BibliographicReference> 
  </xsl:template>
    
 <xsl:template match="dcc:subject">
   <xsl:variable name="subject">
     <xsl:value-of select="." />
   </xsl:variable>
   
   <xsl:for-each select="distinct-values(tokenize($subject, '; '))">
     <dcterms:subject>
       <xsl:value-of select="." />
     </dcterms:subject>
   </xsl:for-each>
   
 </xsl:template>


</xsl:stylesheet>