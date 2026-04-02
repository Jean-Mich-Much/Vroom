<p align="center">
<a href="#english-version">English version</a>
</p>

# 🚀 Vroom : la base de données PHP réinventée

Vroom est un moteur de stockage artisanal en PHP, conçu pour être simple, rapide, lisible et incassable.  
Il repose sur un principe clair :

> 1 enregistrement = 1 fichier, avec un moteur RAM‑centré, multitâche, et une écriture disque 100% atomique.

Vroom est idéal pour les projets qui veulent éviter la complexité d’une base SQL tout en conservant :
- des performances élevées  
- une robustesse maximale  
- un déploiement ultra‑léger  
- une architecture simple à comprendre et à modifier  

---

## 📘 Documentation complète

La documentation HTML complète se trouve ici :  
Fondation/doc/Vroom.html

Elle contient :
- l’architecture interne détaillée  
- le rôle de chaque fichier  
- la description de chaque fonction interne  
- l’API complète  
- des exemples concrets  
- les mécanismes RAM ↔ disque  
- la gestion des locks, transactions, workers, relations, etc.  

---

## 🧩 Fonctionnement général

Vroom combine deux couches :

### 1. Vroom BD
- stockage physique en fichiers `.vrec`  
- header ASCII solide  
- données encodées en Base64  
- marqueur final pour vérifier l’intégrité  
- répartition automatique dans des sous‑dossiers  

### 2. Vroom Moteur
- buffers RAM  
- transactions  
- écriture atomique  
- locks physiques  
- workers parallèles  
- file d’attente  
- cache RAM avec éviction LRU  

---

## ⚙️ API rapide

```php
$id = create('post', ['title' => 'Hello']);
$post = get('post', $id);
update('post', $id, ['title' => 'Modifié']);
delete('post', $id);

begin();
create('log', ['msg' => 'A']);
commit();
```

## 📂 Structure du projet

```text
Vroom.php
Fondation/
 └── vroom/
     ├── data/
     ├── logs/
     └── php/
         ├── Vroom_state.php
         ├── Vroom_id.php
         ├── Vroom_lock.php
         ├── Vroom_disk_read.php
         ├── Vroom_disk_do.php
         ├── Vroom_ram.php
         ├── Vroom_exec.php
         ├── Vroom_job.php
         └── Vroom_relations.php
```

## 🛡️ Sécurité & robustesse

- UTF‑8 strict  
- LF uniquement  
- Base64 pour les données  
- header ASCII  
- écriture atomique  
- locks physiques simples  
- nettoyage automatique  
- marqueur final

## 📜 Objectif

Vroom ne cherche pas à remplacer une base SQL complète.  
Il propose une alternative ultra‑simple, ultra‑fiable et ultra‑performante pour les projets qui veulent aller vite, rester légers, et garder le contrôle total sur leurs données.

## 👤 Auteur

Jean‑Michel G — Bordeaux, France  
iactu.info — depuis 2003

---

<a id="english-version"></a>

# 🚀 Vroom: the reinvented PHP database

Vroom is a handcrafted PHP storage engine designed to be simple, fast, readable and unbreakable.  
It is built on a clear principle:

> 1 record = 1 file, with a RAM‑centered, multitasking engine and 100% atomic disk writes.

Vroom is ideal for projects that want to avoid the complexity of an SQL database while keeping:
- high performance  
- maximum robustness  
- ultra‑light deployment  
- an architecture that is easy to understand and modify  

---

## 📘 Full documentation

The complete HTML documentation is available here:  
Fondation/doc/Vroom_english.html

It contains:
- detailed internal architecture  
- the role of each file  
- the description of every internal function  
- the full API  
- concrete examples  
- RAM ↔ disk mechanisms  
- lock, transaction, worker and relation management  

---

## 🧩 General operation

Vroom combines two layers:

### 1. Vroom DB
- physical storage in `.vrec` files  
- solid ASCII header  
- data encoded in Base64  
- final marker for integrity checks  
- automatic distribution into subfolders  

### 2. Vroom Engine
- RAM buffers  
- transactions  
- atomic write  
- physical locks  
- parallel workers  
- job queue  
- RAM cache with LRU eviction  

---

## ⚙️ Quick API

```php
$id = create('post', ['title' => 'Hello']);
$post = get('post', $id);
update('post', $id, ['title' => 'Updated']);
delete('post', $id);

begin();
create('log', ['msg' => 'A']);
commit();
```

## 📂 Project structure

```text
Vroom.php
Fondation/
 └── vroom/
     ├── data/
     ├── logs/
     └── php/
         ├── Vroom_state.php
         ├── Vroom_id.php
         ├── Vroom_lock.php
         ├── Vroom_disk_read.php
         ├── Vroom_disk_do.php
         ├── Vroom_ram.php
         ├── Vroom_exec.php
         ├── Vroom_job.php
         └── Vroom_relations.php
```

## 🛡️ Security and robustness

- strict UTF‑8  
- LF only  
- Base64 for data  
- ASCII header  
- atomic write  
- simple physical locks  
- automatic cleanup  
- final marker

## 📜 Purpose

Vroom does not aim to replace a full SQL database.  
It offers an ultra‑simple, ultra‑reliable and ultra‑fast alternative for projects that want to move quickly, stay lightweight and keep full control over their data.

## 👤 Author

Jean‑Michel G — Bordeaux, France  
iactu.info — since 2003
