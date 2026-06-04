import os

filepath = r"C:\xampp\htdocs\new-dashboard\static\css\login.css"

# Read raw bytes
with open(filepath, 'rb') as f:
    data = f.read()

# Remove UTF-8 BOM if present
if data[:3] == b'\xef\xbb\xbf':
    print("BOM found and removed")
    data = data[3:]
else:
    print("No BOM found")

# Decode UTF-8
text = data.decode('utf-8')

# Fix double-encoding: UTF-8 bytes were read as CP1252 and re-encoded as UTF-8.
try:
    fixed = text.encode('cp1252').decode('utf-8')
except UnicodeEncodeError:
    # If cp1252 roundtrip fails, handle char by char
    fixed_chars = []
    for ch in text:
        try:
            fixed_chars.append(ch.encode('cp1252').decode('utf-8'))
        except (UnicodeEncodeError, UnicodeDecodeError):
            fixed_chars.append(ch)
    fixed = ''.join(fixed_chars)

# Replace non-ASCII decorative characters with ASCII equivalents
# Curly/smart quotes
fixed = fixed.replace('\u201c', '"')    # left double curly quote
fixed = fixed.replace('\u201d', '"')    # right double curly quote
fixed = fixed.replace('\u2018', "'")    # left single curly quote
fixed = fixed.replace('\u2019', "'")    # right single curly quote
fixed = fixed.replace('\u201a', "'")    # single low-9 quote
fixed = fixed.replace('\u201e', '"')    # double low-9 quote
# Dashes
fixed = fixed.replace('\u2014', '--')   # em dash
fixed = fixed.replace('\u2013', '-')    # en dash
# Ellipsis
fixed = fixed.replace('\u2026', '...')
# Box-drawing characters (decorative lines in comments)
fixed = fixed.replace('\u2500', '-')    # BOX DRAWINGS LIGHT HORIZONTAL
fixed = fixed.replace('\u2504', '-')    # BOX DRAWINGS LIGHT TRIPLE DASH HORIZONTAL
fixed = fixed.replace('\u2550', '=')    # BOX DRAWINGS DOUBLE HORIZONTAL
fixed = fixed.replace('\u2551', '|')    # BOX DRAWINGS DOUBLE VERTICAL
fixed = fixed.replace('\u2501', '-')    # BOX DRAWINGS HEAVY HORIZONTAL
# Euro sign
fixed = fixed.replace('\u20ac', 'EUR')
# Any remaining non-ASCII chars in the ASCII range 128-255 that are mojibake artifacts
# Replace with their closest ASCII equivalent or remove
import unicodedata
cleaned = []
for ch in fixed:
    if ord(ch) < 128:
        cleaned.append(ch)
    elif unicodedata.category(ch).startswith('C'):
        # Control characters, replace with empty or space
        cleaned.append('')
    else:
        # Try to normalize and find ASCII equivalent
        try:
            nfkd = unicodedata.normalize('NFKD', ch)
            ascii_equiv = nfkd.encode('ascii', 'ignore').decode('ascii')
            if ascii_equiv:
                cleaned.append(ascii_equiv)
            else:
                cleaned.append('?')
        except:
            cleaned.append('?')
fixed = ''.join(cleaned)

# Write back as UTF-8 without BOM
with open(filepath, 'w', encoding='utf-8') as f:
    f.write(fixed)

print("File written successfully")

# Verify: check for remaining non-ASCII
with open(filepath, 'rb') as f:
    check = f.read()
non_ascii = [(i, b) for i, b in enumerate(check) if b > 127]
if non_ascii:
    print(f"WARNING: {len(non_ascii)} non-ASCII bytes remain")
else:
    print("All non-ASCII bytes removed")

# Verify BOM is gone
if check[:3] == b'\xef\xbb\xbf':
    print("WARNING: BOM still present!")
else:
    print("No BOM in output file")

# Show first 30 lines
print("\n--- First 30 lines of cleaned file ---")
lines = fixed.split('\n')
for i, line in enumerate(lines[:30]):
    print(f"{i+1}: {line}")