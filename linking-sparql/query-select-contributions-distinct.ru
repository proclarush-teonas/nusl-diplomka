PREFIX dcterms:  <http://purl.org/dc/terms/>
PREFIX contributor:  <http://linked.opendata.cz/resource/dataset/nusl.cz/contributors#>

SELECT ?s ?o WHERE { GRAPH <http://linked.opendata.cz/resource/dataset/nusl.cz> { 
?s dcterms:contributor ?o.
FILTER NOT EXISTS { GRAPH <http://linked.opendata.cz/resource/dataset/nusl.cz/contributors> {
?z contributor:isContributorOf ?y.
FILTER(?s = ?y)
} }
} }