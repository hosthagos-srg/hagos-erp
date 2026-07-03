import zipfile
import sys
import xml.etree.ElementTree as ET

docx = zipfile.ZipFile('HAGOS_Spesifikasi_Sistem_Keuangan_v1.0_1.docx')
xml_content = docx.read('word/document.xml')
tree = ET.XML(xml_content)
namespace = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
text = [node.text for node in tree.findall('.//w:t', namespace) if node.text]

with open('docx_content.txt', 'w', encoding='utf-8') as f:
    f.write('\n'.join(text))
