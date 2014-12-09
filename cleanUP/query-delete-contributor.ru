PREFIX dcterms: <http://purl.org/dc/terms/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
WITH <http://linked.opendata.cz/resource/dataset/nusl.cz> DELETE {
?s dcterms:contributor ?o.
}
WHERE {
?s dcterms:contributor ?o.
FILTER regex(?o, "^.{64}[x]{0,3}%2C%20[x]{0,3}", "i")
}