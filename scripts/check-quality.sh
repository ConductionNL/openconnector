#!/bin/bash

# This script runs all code quality checks on specified PHP files or directories
# Usage: ./scripts/check-quality.sh path/to/file_or_directory

set -e  # Exit on error

# Check if path is provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <path/to/file_or_directory>"
    echo "Example: $0 lib/Service/FileHandlerService.php"
    echo "Example: $0 lib/Service/"
    exit 1
fi

PATH_TO_CHECK=$1

# Check if path exists
if [ ! -e "$PATH_TO_CHECK" ]; then
    echo "Error: Path '$PATH_TO_CHECK' does not exist."
    exit 1
fi

echo "🔍 Running code quality checks on: $PATH_TO_CHECK"

# Create directory for results
RESULTS_DIR="build/quality-reports"
mkdir -p $RESULTS_DIR

echo "📋 Step 1/3: Running PHP_CodeSniffer..."
if command -v phpcs &> /dev/null; then
    phpcs --standard=PSR12 "$PATH_TO_CHECK" | tee "$RESULTS_DIR/phpcs-results.txt"
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        echo "✅ PHP_CodeSniffer passed successfully."
    else
        echo "❌ PHP_CodeSniffer found issues. See $RESULTS_DIR/phpcs-results.txt for details."
        echo "💡 Try running: phpcbf --standard=PSR12 $PATH_TO_CHECK"
    fi
else
    echo "⚠️ Warning: phpcs not found. Skipping PHP_CodeSniffer check."
fi

echo "📋 Step 2/3: Running PHPStan..."
if [ -f "vendor/bin/phpstan" ]; then
    vendor/bin/phpstan analyse "$PATH_TO_CHECK" | tee "$RESULTS_DIR/phpstan-results.txt"
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        echo "✅ PHPStan passed successfully."
    else
        echo "❌ PHPStan found issues. See $RESULTS_DIR/phpstan-results.txt for details."
    fi
else
    echo "⚠️ Warning: PHPStan not found in vendor directory. Run 'composer require --dev phpstan/phpstan' to install."
fi

echo "📋 Step 3/3: Running Psalm..."
if [ -f "vendor/bin/psalm" ]; then
    vendor/bin/psalm "$PATH_TO_CHECK" --no-cache | tee "$RESULTS_DIR/psalm-results.txt"
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        echo "✅ Psalm passed successfully."
    else
        echo "❌ Psalm found issues. See $RESULTS_DIR/psalm-results.txt for details."
    fi
else
    echo "⚠️ Warning: Psalm not found in vendor directory. Run 'composer require --dev vimeo/psalm' to install."
fi

echo ""
echo "📊 Quality Check Summary:"
echo "------------------------"

# Create a summary function
function check_result() {
    RESULT_FILE="$RESULTS_DIR/$1-results.txt"
    if [ -f "$RESULT_FILE" ]; then
        if grep -q "error" "$RESULT_FILE"; then
            echo "❌ $2: Failed"
            return 1
        else
            echo "✅ $2: Passed"
            return 0
        fi
    else
        echo "⚠️ $2: Not run"
        return 2
    fi
}

check_result "phpcs" "PHP_CodeSniffer"
check_result "phpstan" "PHPStan"
check_result "psalm" "Psalm"

echo ""
echo "Report files stored in $RESULTS_DIR/"
echo ""

# Check if all tools passed
if check_result "phpcs" "" > /dev/null && check_result "phpstan" "" > /dev/null && check_result "psalm" "" > /dev/null; then
    echo "🎉 All quality checks passed successfully!"
    exit 0
else
    echo "⚠️ Some quality checks failed. Please fix the issues before committing."
    exit 1
fi 