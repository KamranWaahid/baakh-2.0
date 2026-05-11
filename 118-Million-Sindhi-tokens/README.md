# 🧠 Sindhi Language Mega Corpus – 118M Tokens (JSON + Tokenizer Model)

### 🏛️ Sources:
- Sindh Salamat  
- Sindhi Wikipedia  
- Altaf Shaikh Literary Works  
- Sindhi Language Authority Publications  
- Hamsari Akhbar  
- Pahenji Akhbar  
- Sindhi General Data  

### 📤 Compiled & Uploaded by: Abdul Majid Bhurgiri, *Institute of Language Engineering*  
### 🌐 Official Uploading Credit: [ambile.pk](https://ambile.pk/)  
### 📅 Release Year: 2025  
### 🌍 Language: Sindhi (سنڌي)  
### 📦 Dataset Size: ~118 Million Tokens  
### 🧩 Format: JSON (Structured Text Data + Metadata)  
### 🧠 Includes: Pretrained Sindhi Tokenizer Model  
### 📁 Download: [📥 Google Drive – Sindhi Mega Corpus (118M Tokens)](https://drive.google.com/file/d/1v6g-GJr09BKvPcGgbRip3cOERmbN_4X1/view?usp=sharing)

---

## 📖 Overview

The **Sindhi Language Mega Corpus (118M Tokens)** is a comprehensive, structured Sindhi dataset formatted in **JSON**, designed for **Natural Language Processing (NLP)** and **Language Model (LLM)** training.  
It contains over **118 million Sindhi tokens**, collected from seven major literary, journalistic, and educational sources, and preprocessed into a clean, standardized, machine-readable form.

This release also includes a **custom Sindhi tokenizer model**, specifically trained for Sindhi’s **Arabic-based script**, ensuring accurate token segmentation and compatibility with modern transformer-based architectures.

---

## 📂 Dataset Structure

After extracting the ZIP archive, you will find:

| File / Folder | Description |
|----------------|--------------|
| `data/` | Folder containing multiple Sindhi JSON files (each per category) |
| ├── `sindh_salamat.json` | Literature, philosophy, religion, and culture |
| ├── `sindhi_wikipedia.json` | Encyclopedic and factual text |
| ├── `altaf_shaikh.json` | Altaf Shaikh’s writings and essays |
| ├── `language_authority.json` | Educational and linguistic materials |
| ├── `pahenji_akhbar.json` | News and editorials |
| ├── `hamsari_akhbar.json` | Columns and articles |
| ├── `sindhi_general.json` | General and miscellaneous Sindhi text |
| `tokenizer/` | Directory containing Sindhi tokenizer model files |
| ├── `tokenizer.json` | Core tokenizer configuration (BPE or WordPiece) |
| ├── `vocab.json` | Vocabulary file (Sindhi tokens and subwords) |
| ├── `merges.txt` | Merge rules for byte-pair encoding (if BPE) |
| ├── `special_tokens_map.json` | Mapping of special tokens (e.g., BOS, EOS, PAD) |
| ├── `tokenizer_config.json` | Tokenizer metadata for model loading |
| `README.md` | Documentation file (this one) |

📦 **Format:** JSON files (structured per document or paragraph)  
🧾 **Encoding:** UTF-8  
🧠 **Tokenizer:** Custom trained Sindhi tokenizer (BPE/WordPiece)  

---

## 🧩 JSON Structure

Each JSON file contains an array of structured entries like this:

```json
[
  {
    "id": "salamat_00123",
    "source": "Sindh Salamat",
    "category": "Literature",
    "title": "ادب ۽ فڪر جو تجزيو",
    "text": "سنڌي ادب ۾ فڪر ۽ تخليق جو سفر هڪ ڊگهو ۽ گهرو عمل رهيو آهي...",
    "tokens": 243
  },
  {
    "id": "salamat_00124",
    "source": "Sindh Salamat",
    "category": "Poetry",
    "text": "شاعريءَ ۾ احساس جو اظهار روحاني ۽ جمالياتي ٻنهي سطحن تي ٿيندو آهي...",
    "tokens": 132
  }
]
