#!/bin/bash
# Test manual deploy - rulează comenzile din .cpanel.yml

echo "=========================================="
echo "TEST MANUAL DEPLOY - WebGSM"
echo "=========================================="

export DEPLOYPATH=/home2/webgsm/public_html/test/wp-content

echo "DEPLOYPATH: $DEPLOYPATH"
echo ""

# Verifică dacă există calea
if [ ! -d "$DEPLOYPATH" ]; then
    echo "❌ EROARE: Calea $DEPLOYPATH NU EXISTĂ!"
    echo "Creează folderul:"
    mkdir -pv /home2/webgsm/public_html/test/wp-content
fi

echo "✅ Calea există. Creez subfoldere..."
mkdir -pv $DEPLOYPATH/themes
mkdir -pv $DEPLOYPATH/plugins

echo ""
echo "📂 Conținut curent în themes:"
ls -lah $DEPLOYPATH/themes/ | head -5

echo ""
echo "📂 Conținut curent în plugins:"
ls -lah $DEPLOYPATH/plugins/ | head -5

echo ""
echo "🗑️  Șterg versiunile vechi..."
rm -rfv $DEPLOYPATH/themes/martfury-child
rm -rfv $DEPLOYPATH/plugins/webgsm-b2b-pricing
rm -rfv $DEPLOYPATH/plugins/webgsm-checkout-pro

echo ""
echo "📋 Copiere fișiere..."

# Verifică sursa
if [ ! -d "wp-content/themes/martfury-child" ]; then
    echo "❌ EROARE: Tema martfury-child nu există în repo!"
else
    echo "✅ Copiez martfury-child..."
    cp -Rv wp-content/themes/martfury-child $DEPLOYPATH/themes/
fi

if [ ! -d "wp-content/plugins/webgsm-b2b-pricing" ]; then
    echo "❌ EROARE: Plugin webgsm-b2b-pricing nu există în repo!"
else
    echo "✅ Copiez webgsm-b2b-pricing..."
    cp -Rv wp-content/plugins/webgsm-b2b-pricing $DEPLOYPATH/plugins/
fi

if [ ! -d "wp-content/plugins/webgsm-checkout-pro" ]; then
    echo "❌ EROARE: Plugin webgsm-checkout-pro nu există în repo!"
else
    echo "✅ Copiez webgsm-checkout-pro..."
    cp -Rv wp-content/plugins/webgsm-checkout-pro $DEPLOYPATH/plugins/
fi

echo ""
echo "🔒 Setez permisiuni..."
chmod -R 755 $DEPLOYPATH/themes/martfury-child
chmod -R 755 $DEPLOYPATH/plugins/webgsm-b2b-pricing
chmod -R 755 $DEPLOYPATH/plugins/webgsm-checkout-pro

echo ""
echo "✅ VERIFICARE FINALĂ:"
echo "─────────────────────────────────────────"
echo "Tema:"
ls -lah $DEPLOYPATH/themes/martfury-child/ | head -3

echo ""
echo "Plugin B2B:"
ls -lah $DEPLOYPATH/plugins/webgsm-b2b-pricing/ | head -3

echo ""
echo "Plugin Checkout:"
ls -lah $DEPLOYPATH/plugins/webgsm-checkout-pro/ | head -3

echo ""
echo "=========================================="
echo "✅ DEPLOY MANUAL FINALIZAT!"
echo "Data: $(date)"
echo "=========================================="
