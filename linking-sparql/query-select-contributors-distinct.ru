PREFIX dcterms:  <http://purl.org/dc/terms/>
prefix foaf: <http://xmlns.com/foaf/0.1/>

SELECT DISTINCT ?o WHERE { GRAPH <http://linked.opendata.cz/resource/dataset/nusl.cz> { 
?s dcterms:contributor ?o.
FILTER NOT EXISTS { GRAPH <http://linked.opendata.cz/resource/dataset/nusl.cz/contributors> {
?z foaf:name ?y.
FILTER(?o = ?z)
} }
} }
