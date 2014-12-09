PREFIX dcterms: <http://purl.org/dc/terms/>

SELECT ?o WHERE {?s dcterms:contributor ?o.
FILTER regex(?o, "^.{64}[x]{0,3}%2C%20[x]{0,3}", "i")
}