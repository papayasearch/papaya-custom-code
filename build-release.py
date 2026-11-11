from pathlib import Path
import hashlib, os, re, zipfile
root = Path(__file__).resolve().parent
source = root
version = re.search(r'^ \* Version: (.+)$', (source / 'papaya-custom-code.php').read_text(), re.M).group(1).strip()
tag = os.environ.get('RELEASE_TAG', 'v' + version)
if tag != 'v' + version:
    raise SystemExit('Release tag does not match the plugin Version header')
if not (source / 'plugin-update-checker/plugin-update-checker.php').is_file():
    raise SystemExit('Bundled update checker is missing')
output = root / 'dist'
output.mkdir(exist_ok=True)
archive = output / 'papaya-custom-code.zip'
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as z:
    for p in sorted(source.rglob('*')):
        if p.is_file() and p.relative_to(source).parts[0] in {'papaya-custom-code.php', 'storage.php', 'php-editor.php', 'code-editors.php', 'github-updates.php', 'index.php', 'README.txt', 'assets', 'custom-code', 'plugin-update-checker'}:
            z.write(p, Path('papaya-custom-code') / p.relative_to(source))
with zipfile.ZipFile(archive) as z:
    if z.testzip(): raise SystemExit('Archive validation failed')
(output / 'SHA256SUMS').write_text(hashlib.sha256(archive.read_bytes()).hexdigest() + '  papaya-custom-code.zip\n')
print(archive)
