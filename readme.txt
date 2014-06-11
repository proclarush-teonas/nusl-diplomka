nusl-diplomka

Tento repozitáø obsahuje práci na projektu zpøístupnìní šedé literatury jako linked data.

Data se stahují z Národního úložištì šedé literatury (NUSL).
Ukázku formátu stažených dat lze najít v adresáøi "files_format_preview", stejnì tak jako ukázku dat po XSLT transformaci. 
Pro XSLT je použit saxon a knihovna komunikující s ním v jazyce PHP, oboje je taktéž pøiloženo (adr. saxon, xml).

Adresáø bat_files obsahuje dávky pro spuštìní PHP které stahuje a zpracovává data (soubor run-php.bat) a dávku pro dump dat z Virtuosa a uložení do souboru.

Soubor config.ini obsahuje promìnné které lze pøizpùsobit konkrétním podmínkám spouštìní aplikace.

Soubor tps.php se stará o stažení a transformaci dat, také o plynulý prùbìh aplikace.

Soubor upload.php zaøizuje nahrávání dat do grafu ve Virtuosu.