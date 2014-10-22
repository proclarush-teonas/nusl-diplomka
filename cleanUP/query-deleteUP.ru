PREFIX dcterms: <http://purl.org/dc/terms/>
PREFIX biro: <http://purl.org/spar/biro/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

WITH <http://linked.opendata.cz/resource/dataset/nusl.cz> DELETE {
?s dcterms:created ?o.
}
WHERE {
?s rdf:type biro:BibliographicRecord.
?s dcterms:created ?o.
?s dcterms:created ?p.
FILTER (?o != ?p && ?p > ?o)
}