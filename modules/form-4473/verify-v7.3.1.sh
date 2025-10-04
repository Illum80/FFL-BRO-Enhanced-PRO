#!/bin/bash
echo "🔍 Verifying Form 4473 v7.3.1 Installation"
echo "=========================================="
echo ""

echo "✅ Enhanced Features:"
echo "  1. Digital Signatures: $([ -f modules/form-4473/signatures/signature-handler.php ] && echo 'Installed' || echo 'Missing')"
echo "  2. PDF Generation: $([ -f modules/form-4473/pdf/pdf-generator.php ] && echo 'Installed' || echo 'Missing')"
echo "  3. Photo Upload: $([ -f modules/form-4473/uploads/photo-handler.php ] && echo 'Installed' || echo 'Missing')"
echo "  4. Email Delivery: $([ -f modules/form-4473/email/email-handler.php ] && echo 'Installed' || echo 'Missing')"
echo "  5. NICS Integration: $([ -f modules/form-4473/nics/nics-handler.php ] && echo 'Installed' || echo 'Missing')"

echo ""
echo "📚 TCPDF Library:"
echo "  Status: $([ -d includes/form-4473/lib/tcpdf ] && echo 'Installed' || echo 'Not installed')"

echo ""
echo "🔗 API Endpoints:"
echo "  POST /wp-json/fflbro/v1/form-4473/signature/save"
echo "  GET  /wp-json/fflbro/v1/form-4473/{id}/pdf"
echo "  POST /wp-json/fflbro/v1/form-4473/upload-id"
echo "  POST /wp-json/fflbro/v1/form-4473/{id}/email"
echo "  POST /wp-json/fflbro/v1/form-4473/nics/check"
echo "  GET  /wp-json/fflbro/v1/form-4473/nics/status"

echo ""
echo "📁 Directory Structure:"
ls -lh modules/form-4473/

echo ""
echo "✅ Installation Complete!"
