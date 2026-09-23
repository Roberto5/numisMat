# Piano di implementazione numisMat

## Problema e approccio

Realizzare da zero numisMat, un'applicazione web per catalogare monete con dati testuali e immagini. Il catalogo sarà consultabile pubblicamente; un unico amministratore autenticato potrà gestire i record. La soluzione userà html5 e js con php e mysql e una cartella immagini. l'appplcazione userà il database di numista per trovare informazioni e immagini.

## Scelte tecniche

- **Frontend/backend:** HTML5, CSS3, JavaScript php
- **Database:** MySQL
- **Autenticazione:** sessione sicura con credenziali dell'amministratore configurate tramite variabili d'ambiente/secrets; nessuna registrazione pubblica.
- **Immagini:** file nel volume Docker `uploads`, metadati e percorsi in mysql; upload fronte/retro.

## struttura del progetto
