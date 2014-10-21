<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:p="http://www.openarchives.org/OAI/2.0/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcc="http://purl.org/dc/elements/1.1/" xmlns:biro="http://purl.org/spar/biro/" xmlns:bibo="http://purl.org/ontology/bibo/" xmlns:ld="http://linked.opendata.cz/resource/dataset/nusl.cz/" xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:wt="http://whatever"  version='2.0' exclude-result-prefixes="p oai_dc dcc wt"> 
<xsl:output method="xml" indent="yes" encoding="utf-8" normalization-form="NFC" />

<xsl:function name="wt:uri-encode">
  <xsl:param name="input"/>
  <xsl:value-of select="encode-for-uri($input)"/>
</xsl:function>
  
  <xsl:variable name="myuri">http://linked.opendata.cz/resource/dataset/nusl.cz/</xsl:variable>
  <xsl:variable name="pshuri">http://linked.opendata.cz/resource/dataset/psh.ntkcz.cz/</xsl:variable>
  <xsl:variable name="myrec">bibliographic-record/</xsl:variable>
  <xsl:variable name="myexp">expression/</xsl:variable>

  <xsl:template match='p:OAI-PMH'>
  <rdf:RDF>
    <xsl:apply-templates select="p:ListRecords" />    
  </rdf:RDF>
  </xsl:template>
  
  <xsl:template match="p:ListRecords">
    <xsl:apply-templates select="p:record" />
  </xsl:template>
  
  
  <xsl:template match="p:record">
    <xsl:variable name="typeof" select="p:metadata/oai_dc:dc/dcc:type/." />   
    <xsl:if test="contains($typeof, 'Thesis')">
      <xsl:apply-templates select="p:header" />
      <xsl:apply-templates select="p:metadata/oai_dc:dc" />
    </xsl:if>     
  </xsl:template>
  
  <xsl:template match="p:header">
            
    <xsl:variable name="ajdy">
      <xsl:value-of select="wt:uri-encode(substring-after(substring-after(p:identifier, ':'), ':'))" />
    </xsl:variable>
    <biro:BibliographicRecord rdf:about="{$myuri}{$myrec}{$ajdy}">
      <dcterms:identifier>        
        <xsl:value-of select="$ajdy" />
      </dcterms:identifier>
      <biro:references rdf:resource="{$myuri}{$myexp}{$ajdy}" />
      
      <dcterms:created rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">
        <xsl:analyze-string select="p:datestamp" regex="[:0-9TZ-]">
          <xsl:matching-substring>
            <xsl:value-of select="." />
          </xsl:matching-substring>
        </xsl:analyze-string>            
      </dcterms:created>
     
    </biro:BibliographicRecord>
  </xsl:template>
  
  <xsl:template match="p:metadata/oai_dc:dc">
    <xsl:variable name="ajdy">
      <xsl:value-of select="substring-after(dcc:identifier, '-')" />
    </xsl:variable>        
    <bibo:Document rdf:about="{$myuri}{$myexp}{$ajdy}">            
      <biro:isReferencedBy rdf:resource="{$myuri}{$myrec}{$ajdy}" />
      
      <rdfs:seeAlso rdf:resource="{dcc:identifier}" />
      
      <dcterms:title>
        <xsl:value-of select="dcc:title" />
      </dcterms:title>      
      
      <dcterms:creator>
        <xsl:value-of select="dcc:creator" />
      </dcterms:creator>      
      
      <xsl:apply-templates select="dcc:contributor" />
      
      
      <xsl:apply-templates select="dcc:subject" />
      
      <dcterms:description>
        <xsl:value-of select="dcc:description" />
      </dcterms:description>      
      
      <xsl:apply-templates select="dcc:publisher" />
      
            
      <dcterms:created rdf:datatype="http://www.w3.org/2001/XMLSchema#date">
        <xsl:analyze-string select="dcc:date" regex="[0-9-]">
          <xsl:matching-substring>
            <xsl:value-of select="." />
          </xsl:matching-substring>
        </xsl:analyze-string>
      </dcterms:created>
      <dcterms:type>
        <!-- nejaky lepsi typ, s prolinkovanim -->
        <xsl:value-of select="dcc:type" />
      </dcterms:type>
      
      <xsl:apply-templates select="dcc:language" />
      
    </bibo:Document> 
  </xsl:template>
    
 <xsl:template match="dcc:subject">
   <xsl:variable name="subject">
     <xsl:value-of select="." />
   </xsl:variable>
   
   <xsl:for-each select="distinct-values(tokenize($subject, '; '))">
    <xsl:variable name="subsubject">
     <xsl:value-of select="wt:uri-encode(.)" />
   </xsl:variable>
   
    <dcterms:subject> 
     <skos:Concept rdf:about="{$pshuri}{$subsubject}"> 
      <skos:prefLabel><xsl:value-of select="." /></skos:prefLabel>
     </skos:Concept>     
    </dcterms:subject>          
   </xsl:for-each>
   
 </xsl:template>
 
 <xsl:template match="dcc:contributor">
  <dcterms:contributor>
   <xsl:value-of select="." />
  </dcterms:contributor>
 </xsl:template>

 <xsl:template match="dcc:publisher">
  <dcterms:publisher>
    <xsl:value-of select="." />
  </dcterms:publisher>
 </xsl:template>
       
 <xsl:template match="dcc:language[string-length() != 0]">
  <xsl:variable name="language">
   <xsl:value-of select="." />
  </xsl:variable>
   
  <xsl:for-each select="distinct-values(tokenize($language, ' '))">
   <dcterms:language>
     <xsl:value-of select="." />
   </dcterms:language>
  </xsl:for-each>
  
 </xsl:template>
  
</xsl:stylesheet>