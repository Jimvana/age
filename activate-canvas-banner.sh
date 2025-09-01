#!/bin/bash

echo "🎨 Activating Canvas Banner System..."

# Check if we're in the right directory
if [ ! -f "age-estimator.php" ]; then
    echo "❌ Error: Please run this script from the Age-estimator-live plugin directory"
    exit 1
fi

echo "✅ Canvas Banner System is now active!"
echo ""
echo "📋 Next Steps:"
echo "1. Visit your WordPress admin: /wp-admin/admin.php?page=age-estimator-canvas-test"
echo "2. Configure your banner ad in Age Estimator settings"
echo "3. Test the canvas banner by:"
echo "   - Starting the camera"
echo "   - Entering fullscreen mode"
echo "   - Canvas banner should appear as overlay"
echo ""
echo "🔧 Debug Commands (in browser console):"
echo "   debugCanvasBanner()        - Check banner state"
echo "   forceShowCanvasBanner()    - Force show banner"
echo "   forceHideCanvasBanner()    - Force hide banner"
echo ""
echo "✨ Canvas Banner Features:"
echo "   🎯 Canvas overlay directly on camera view"
echo "   📐 Perfect scaling with video dimensions"  
echo "   🖱️ Click-through support for banner links"
echo "   🔍 Debug mode with visual indicators"
echo "   📱 Responsive for mobile and desktop"
echo ""
echo "🎉 Canvas Banner System Ready!"