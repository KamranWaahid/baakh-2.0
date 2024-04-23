// Replace Sindhi words with English words
function replaceWords(inputText, replacementMap) {
    // Split the input text into individual words
    var words = inputText.split(' ');
  
    // Iterate over each word
    var replacedWords = words.map(function(word) {
      // Check if the word exists in the replacement map
      if (replacementMap.hasOwnProperty(word)) {
        // Replace the word with its corresponding English word
        return replacementMap[word];
      }
      
      // If the word is not found in the replacement map, return the original word
      return word;
    });
  
    // Join the replaced words back into a single string
    var replacedText = replacedWords.join(' ');
  
    return replacedText;
  }
  
  // Load the Sindhi words and their English replacements
  async function loadReplacementMap() {
    try {
      const response = await fetch(myFilesUrl + 'all_words.txt');
      if (!response.ok) {
        throw new Error('Failed to fetch the Sindhi words file');
      }
      const fileContent = await response.text();
  
      // Parse the Sindhi words file content into a replacement map object
      const lines = fileContent.split('\n');
      var replacementMap = {};
      lines.forEach(function(line) {
        var parts = line.split(':');
        if (parts.length === 2) {
          var sindhiWord = parts[0].trim();
          var englishWord = parts[1].trim();
          replacementMap[sindhiWord] = englishWord;
        }
      });
  
      return replacementMap;
    } catch (error) {
      console.error('Error:', error);
      throw error;
    }
  }
  
  // Replace the input text with Sindhi words converted to English
  async function convertText() {
    var inputText = document.getElementById('couplet_text').value;
    var replacementMap = await loadReplacementMap();
    var replacedText = replaceWords(inputText, replacementMap);
    document.getElementById('roman_txt').value = replacedText;
  }
  