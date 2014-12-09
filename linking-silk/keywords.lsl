<Silk>

  <Prefixes>
    <Prefix id="rdf" namespace="http://www.w3.org/1999/02/22-rdf-syntax-ns#" />
    <Prefix id="skos" namespace="http://www.w3.org/2004/02/skos/core#" />
    <Prefix id="owl" namespace="http://www.w3.org/2002/07/owl#" />
    
  </Prefixes>

  <DataSources>
    <DataSource id="nusl" type="sparqlEndpoint">
      <Param name="endpointURI" value="http://localhost:8890/sparql" />
      <Param name="graph" value="http://linked.opendata.cz/resource/dataset/nusl.cz" />
    </DataSource>

    <DataSource id="psh" type="sparqlEndpoint">
      <Param name="endpointURI" value="http://localhost:8890/sparql" />
      <Param name="graph" value="http://psh.ntkcz.cz/skos/" />
    </DataSource>
  </DataSources>

  <Interlinks>
    <Interlink id="keywords">
      <LinkType>owl:sameAs</LinkType>

      <SourceDataset dataSource="nusl" var="a">
        <RestrictTo>
          ?a a skos:Concept
        </RestrictTo>
      </SourceDataset>

      <TargetDataset dataSource="psh" var="b">
        <RestrictTo>
          ?b a skos:Concept
        </RestrictTo>
      </TargetDataset>

      <LinkageRule>
          <Compare metric="equality" threshold="0.0">
            <TransformInput function="lowerCase">
              <Input path="?a/skos:prefLabel"/>
            </TransformInput>
            <TransformInput function="lowerCase">
              <Input path="?b/skos:prefLabel"/>
            </TransformInput>
          </Compare>
      </LinkageRule>

      <Filter limit="1" />

      <Outputs>
        <Output type="file" minConfidence="1.00">
          <Param name="file" value="LinkedKeywords.nt" />
          <Param name="format" value="ntriples" />
        </Output>
      </Outputs>
    </Interlink>
  </Interlinks>

</Silk>