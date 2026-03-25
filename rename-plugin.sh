#!/bin/bash

# make sure this script is executable
# chmod +x rename-plugin.sh

# run this script with command:
# ./rename-plugin.sh

set -e  # Exit on any error

# Function to escape special regex characters
escape_regex() {
    printf '%s\n' "$1" | sed "s/[[\.*^$()+?{|]/\\\\&/g"
}

# Function to escape replacement string for sed (escape & and \)
escape_replacement() {
    printf '%s\n' "$1" | sed 's/\\/\\\\/g; s/&/\\&/g'
}

# Cross-platform sed in-place function
sed_inplace() {
    local pattern="$1"
    local file="$2"
    
    # Check if we're on macOS (BSD sed) or Linux (GNU sed)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS requires empty string for backup suffix
        sed -i "" "$pattern" "$file"
    else
        # Linux and other Unix systems
        sed -i "$pattern" "$file"
    fi
}

# Function to validate input
validate_input() {
    local input="$1"
    local type="$2"
    
    if [ -z "$input" ]; then
        echo "Error: $type cannot be empty"
        return 1
    fi
    
    if [ "$type" = "slug" ]; then
        if [[ ! "$input" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]]; then
            echo "Error: Slug format should only contain lowercase letters, numbers, and hyphens (e.g., 'my-plugin')"
            return 1
        fi
    fi
    
    return 0
}

# Fixed old plugin names
OLD_NAME="plugin-name"
OLD_NAME_CAP="PluginName"

echo "=== WordPress Plugin Renamer ==="
echo "This script will rename '$OLD_NAME' to your new plugin name."
echo ""

# Prompt user for the new plugin names with validation
while true; do
    read -r -p "Enter the new plugin name (slug format, e.g., 'new-plugin'): " NEW_NAME
    if validate_input "$NEW_NAME" "slug"; then
        break
    fi
done

while true; do
    read -r -p "Enter the new plugin name (readable format, e.g., 'New Plugin'): " NEW_NAME_CAP
    if validate_input "$NEW_NAME_CAP" "readable"; then
        break
    fi
done

# Prompt for plugin type (FREE or PRO, default FREE)
read -r -p "Plugin type (FREE/PRO, default: FREE, press Enter for FREE): " PLUGIN_TYPE_INPUT
PLUGIN_TYPE=$(echo "$PLUGIN_TYPE_INPUT" | tr '[:lower:]' '[:upper:]')
if [ -z "$PLUGIN_TYPE" ]; then
    PLUGIN_TYPE="FREE"
fi
if [ "$PLUGIN_TYPE" != "FREE" ] && [ "$PLUGIN_TYPE" != "PRO" ]; then
    echo "Error: Invalid input. Defaulting to FREE."
    PLUGIN_TYPE="FREE"
fi

# If FREE, skip licensing-related prompts and delete includes folder
if [ "$PLUGIN_TYPE" = "FREE" ]; then
    echo "FREE version selected. Includes folder will be removed after renaming."
    # Set default values for licensing-related prompts (won't be used for FREE)
    KEY_PREFIX=""
    TEXTDOMAIN="$NEW_NAME"
    CLASS_PREFIX=""
else
    # Prompt for key_prefix (required for PRO)
    while true; do
        read -r -p "Enter key prefix (for constants/filters, e.g., 'my_plugin'): " KEY_PREFIX
        if validate_input "$KEY_PREFIX" "readable"; then
            break
        fi
    done

    # Prompt for textdomain (defaults to plugin slug)
    read -r -p "Enter textdomain (default: '$NEW_NAME', press Enter to use default): " TEXTDOMAIN
    if [ -z "$TEXTDOMAIN" ]; then
        TEXTDOMAIN="$NEW_NAME"
    fi

    # Convert key_prefix to class name format for default display (dash to underscore, then PascalCase)
    CLASS_PREFIX_DEFAULT=$(echo "$KEY_PREFIX" | tr '-' '_' | awk -F'_' '{for(i=1;i<=NF;i++)if(length($i)>0)$i=toupper(substr($i,1,1))tolower(substr($i,2));print}' OFS='_')
    
    # Prompt for class_prefix (defaults to converted key_prefix)
    read -r -p "Enter class prefix (default: '$CLASS_PREFIX_DEFAULT', press Enter to use default): " CLASS_PREFIX
    if [ -z "$CLASS_PREFIX" ]; then
        CLASS_PREFIX="$CLASS_PREFIX_DEFAULT"
    fi
fi

# Confirm inputs
echo ""
echo "=== Summary ==="
echo "Old slug name: '$OLD_NAME' -> New slug name: '$NEW_NAME'"
echo "Old readable name: '$OLD_NAME_CAP' -> New readable name: '$NEW_NAME_CAP'"
echo "Plugin type: '$PLUGIN_TYPE'"
if [ "$PLUGIN_TYPE" = "PRO" ]; then
    echo "Key prefix: '$KEY_PREFIX'"
    echo "Textdomain: '$TEXTDOMAIN'"
    echo "Class prefix: '$CLASS_PREFIX'"
fi
echo ""
read -r -p "Is this correct? (y/n) " CONFIRM

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "Operation cancelled."
    exit 1
fi

echo ""
echo "=== Starting rename process ==="

# Escape the names for use in regex
OLD_NAME_ESCAPED=$(escape_regex "$OLD_NAME")
NEW_NAME_ESCAPED=$(escape_regex "$NEW_NAME")
OLD_NAME_CAP_ESCAPED=$(escape_regex "$OLD_NAME_CAP")
NEW_NAME_CAP_ESCAPED=$(escape_regex "$NEW_NAME_CAP")

# Step 1: Rename files and directories containing the old slug name
echo "Step 1: Renaming files and directories..."
find . -path "./.git" -prune -o -path "./rename-plugin.sh" -prune -o -name "*$OLD_NAME*" -type f -print | while read -r file; do
    if [ -n "$file" ]; then
        newfile="${file//$OLD_NAME/$NEW_NAME}"
        if [ "$file" != "$newfile" ]; then
            echo "  Renaming file: $file -> $newfile"
            mv "$file" "$newfile" || echo "  Warning: Failed to rename $file"
        fi
    fi
done

find . -path "./.git" -prune -o -path "./rename-plugin.sh" -prune -o -name "*$OLD_NAME*" -type d -print | while read -r dir; do
    if [ -n "$dir" ]; then
        newdir="${dir//$OLD_NAME/$NEW_NAME}"
        if [ "$dir" != "$newdir" ]; then
            echo "  Renaming directory: $dir -> $newdir"
            mv "$dir" "$newdir" || echo "  Warning: Failed to rename $dir"
        fi
    fi
done

# Step 2: Replace text inside files
echo "Step 2: Replacing text content..."

# Replace slug name in text files
echo "  Replacing '$OLD_NAME' with '$NEW_NAME'..."
if files_with_old_name=$(grep -rl --exclude-dir=".git" --exclude="rename-plugin.sh" --binary-files=without-match "$OLD_NAME" . 2>/dev/null); then
    while IFS= read -r file; do
        if [ -f "$file" ] && [ -w "$file" ]; then
            echo "    Processing: $file"
            sed_inplace "s|$OLD_NAME_ESCAPED|$NEW_NAME_ESCAPED|g" "$file" || echo "    Warning: Failed to process $file"
        fi
    done <<< "$files_with_old_name"
else
    echo "  No files found containing '$OLD_NAME'"
fi

# Replace capitalized name in text files
echo "  Replacing '$OLD_NAME_CAP' with '$NEW_NAME_CAP'..."
if files_with_old_cap=$(grep -rl --exclude-dir=".git" --exclude="rename-plugin.sh" --binary-files=without-match "$OLD_NAME_CAP" . 2>/dev/null); then
    while IFS= read -r file; do
        if [ -f "$file" ] && [ -w "$file" ]; then
            echo "    Processing: $file"
            sed_inplace "s|$OLD_NAME_CAP_ESCAPED|$NEW_NAME_CAP_ESCAPED|g" "$file" || echo "    Warning: Failed to process $file"
        fi
    done <<< "$files_with_old_cap"
else
    echo "  No files found containing '$OLD_NAME_CAP'"
fi

echo ""
echo "=== Verification ==="
echo "Checking for any remaining references..."

remaining_slug=$(grep -r --exclude-dir=".git" --exclude="rename-plugin.sh" --binary-files=without-match "$OLD_NAME" . 2>/dev/null | wc -l)
remaining_cap=$(grep -r --exclude-dir=".git" --exclude="rename-plugin.sh" --binary-files=without-match "$OLD_NAME_CAP" . 2>/dev/null | wc -l)

if [ "$remaining_slug" -gt 0 ] || [ "$remaining_cap" -gt 0 ]; then
    echo "Warning: Found $remaining_slug remaining '$OLD_NAME' and $remaining_cap remaining '$OLD_NAME_CAP' references"
    echo "You may need to manually review these files:"
    if [ "$remaining_slug" -gt 0 ]; then
        grep -r --exclude-dir=".git" --exclude="rename-plugin.sh" --binary-files=without-match "$OLD_NAME" . 2>/dev/null | head -5
    fi
    if [ "$remaining_cap" -gt 0 ]; then
        grep -r --exclude-dir=".git" --exclude="rename-plugin.sh" --binary-files=without-match "$OLD_NAME_CAP" . 2>/dev/null | head -5
    fi
else
    echo "✓ All references have been successfully renamed!"
fi

# Only process licensing files for PRO version
if [ "$PLUGIN_TYPE" = "PRO" ]; then
    # Process main file for PRO version first
    echo ""
    echo "=== Processing main file for PRO version ==="
    
    # Find main plugin file (should be renamed already)
    MAIN_FILE=$(find . -maxdepth 1 -type f -name "$NEW_NAME.php" | head -1)
    if [ -z "$MAIN_FILE" ]; then
        # Fallback to plugin-name.php if not renamed yet
        MAIN_FILE="./plugin-name.php"
    fi
    
    if [ -f "$MAIN_FILE" ]; then
        echo "  Processing: $(basename "$MAIN_FILE")"
        
        # Convert class_prefix to file naming format (lowercase, underscore to dash)
        CLASS_PREFIX_FILE=$(echo "$CLASS_PREFIX" | tr '_' '-' | tr '[:upper:]' '[:lower:]')
        CLASS_PREFIX_FILE_REPL=$(escape_replacement "$CLASS_PREFIX_FILE")
        
        # Convert key_prefix to uppercase constant format (for constant names: uppercase, dash to underscore)
        KEY_PREFIX_UPPER=$(echo "$KEY_PREFIX" | tr '[:lower:]' '[:upper:]' | tr '-' '_')
        KEY_PREFIX_UPPER_REPL=$(escape_replacement "$KEY_PREFIX_UPPER")
        
        # Convert key_prefix to lowercase format (for filter/meta/option names: keep original format, just lowercase)
        KEY_PREFIX_LOWER=$(echo "$KEY_PREFIX" | tr '[:upper:]' '[:lower:]')
        KEY_PREFIX_LOWER_REPL=$(escape_replacement "$KEY_PREFIX_LOWER")
        
        # Convert class_prefix to namespace/class name format (PascalCase with underscores preserved)
        CLASS_PREFIX_NAMESPACE=$(echo "$CLASS_PREFIX" | awk -F'_' '{for(i=1;i<=NF;i++)if(length($i)>0)$i=toupper(substr($i,1,1))tolower(substr($i,2));print}' OFS='_')
        CLASS_PREFIX_NAMESPACE_REPL=$(escape_replacement "$CLASS_PREFIX_NAMESPACE")
        CLASS_PREFIX_CLASS=$(echo "$CLASS_PREFIX" | awk -F'_' '{for(i=1;i<=NF;i++)if(length($i)>0)$i=toupper(substr($i,1,1))tolower(substr($i,2));print}' OFS='_')
        CLASS_PREFIX_CLASS_REPL=$(escape_replacement "$CLASS_PREFIX_CLASS")
        
        # Replace {{key_prefix}} in constant names with uppercase format (dash to underscore)
        sed_inplace "s|{{key_prefix}}_PLUGIN_FILE|${KEY_PREFIX_UPPER_REPL}_PLUGIN_FILE|g" "$MAIN_FILE"
        sed_inplace "s|{{key_prefix}}_PLUGIN_BASENAME|${KEY_PREFIX_UPPER_REPL}_PLUGIN_BASENAME|g" "$MAIN_FILE"
        sed_inplace "s|{{key_prefix}}_PLUGIN_SLUG|${KEY_PREFIX_UPPER_REPL}_PLUGIN_SLUG|g" "$MAIN_FILE"
        sed_inplace "s|{{key_prefix}}_PLUGIN_PATH|${KEY_PREFIX_UPPER_REPL}_PLUGIN_PATH|g" "$MAIN_FILE"
        sed_inplace "s|{{key_prefix}}_PLUGIN_URL|${KEY_PREFIX_UPPER_REPL}_PLUGIN_URL|g" "$MAIN_FILE"
        sed_inplace "s|{{key_prefix}}_INCLUDES_PATH|${KEY_PREFIX_UPPER_REPL}_INCLUDES_PATH|g" "$MAIN_FILE"
        sed_inplace "s|{{key_prefix}}_VERSION_NUM|${KEY_PREFIX_UPPER_REPL}_VERSION_NUM|g" "$MAIN_FILE"
        
        # Replace {{textdomain}} in string values
        TEXTDOMAIN_REPL=$(escape_replacement "$TEXTDOMAIN")
        sed_inplace "s|'{{textdomain}}'|'$TEXTDOMAIN_REPL'|g" "$MAIN_FILE"
        sed_inplace "s|\"{{textdomain}}\"|\"$TEXTDOMAIN_REPL\"|g" "$MAIN_FILE"
        
        # Replace __FILE__ with the constant (after constants are defined)
        sed_inplace "s|plugin_dir_path( __FILE__ )|plugin_dir_path( ${KEY_PREFIX_UPPER_REPL}_PLUGIN_FILE )|g" "$MAIN_FILE"
        sed_inplace "s|plugin_dir_url( __FILE__ )|plugin_dir_url( ${KEY_PREFIX_UPPER_REPL}_PLUGIN_FILE )|g" "$MAIN_FILE"
        sed_inplace "s|plugin_basename( __FILE__ )|plugin_basename( ${KEY_PREFIX_UPPER_REPL}_PLUGIN_FILE )|g" "$MAIN_FILE"
        
        # Replace YMMVPL_INCLUDES_PATH with the replaced constant
        sed_inplace "s|YMMVPL_INCLUDES_PATH|${KEY_PREFIX_UPPER_REPL}_INCLUDES_PATH|g" "$MAIN_FILE"
        
        # Replace {{class_prefix}} in class filename
        sed_inplace "s|class-{{class_prefix}}-init|class-$CLASS_PREFIX_FILE_REPL-init|g" "$MAIN_FILE"
        
        # Replace namespace and class references (e.g., \YMMVPL\YMMVPL_Init::init())
        sed_inplace "s|\\\\YMMVPL\\\\YMMVPL_Init::init()|\\\\${CLASS_PREFIX_NAMESPACE_REPL}\\\\${CLASS_PREFIX_CLASS_REPL}_Init::init()|g" "$MAIN_FILE"
        sed_inplace "s|\\\\YMMVPL\\\\|\\\\${CLASS_PREFIX_NAMESPACE_REPL}\\\\|g" "$MAIN_FILE"
        sed_inplace "s|class-ymmvpl-init|class-$CLASS_PREFIX_FILE_REPL-init|g" "$MAIN_FILE"
        
        echo "  ✓ Main file processed (constants replaced, __FILE__, INCLUDES_PATH, namespace and class references replaced)"
    else
        echo "  Warning: Main plugin file not found"
    fi
    
    echo ""
    echo "=== License files rename (includes/) ==="
    # Convert class_prefix to file naming format (lowercase, underscore to dash)
    CLASS_PREFIX_FILE=$(echo "$CLASS_PREFIX" | tr '_' '-' | tr '[:upper:]' '[:lower:]')

    # Convert class_prefix to namespace/class name format (PascalCase with underscores preserved)
    CLASS_PREFIX_CLASS=$(echo "$CLASS_PREFIX" | awk -F'_' '{for(i=1;i<=NF;i++)if(length($i)>0)$i=toupper(substr($i,1,1))tolower(substr($i,2));print}' OFS='_')
    CLASS_PREFIX_NAMESPACE="$CLASS_PREFIX_CLASS"

    # Convert key_prefix to uppercase for constant names (uppercase, dash to underscore)
    KEY_PREFIX_UPPER=$(echo "$KEY_PREFIX" | tr '[:lower:]' '[:upper:]' | tr '-' '_')
    
    # Convert key_prefix to lowercase for filter/meta/option names (keep original format, just lowercase)
    KEY_PREFIX_LOWER=$(echo "$KEY_PREFIX" | tr '[:upper:]' '[:lower:]')

    # Rename filenames: class-ymmvpl*.php -> class-{class_prefix_file}*.php
    echo "  Renaming class files..."
    mapfile -t __ymmvpl_files < <(find ./includes -maxdepth 1 -type f -name "class-ymmvpl*.php" 2>/dev/null || true)
    if [ ${#__ymmvpl_files[@]} -eq 0 ]; then
        echo "  No files matching 'class-ymmvpl*.php' found in includes/."
    else
        for __f in "${__ymmvpl_files[@]}"; do
        __base="$(basename "$__f")"
        __dir="$(dirname "$__f")"
            __new_base="${__base/class-ymmvpl/class-$CLASS_PREFIX_FILE}"
        if [ "$__base" != "$__new_base" ]; then
                echo "    Renaming file: $__base -> $__new_base"
                mv "$__f" "$__dir/$__new_base" || echo "    Warning: Failed to rename $__f"
        fi
    done
fi

    # Replace placeholders and references in includes/*.php files (including subdirectories)
    echo "  Replacing placeholders in includes files..."
    if includes_files=$(find ./includes -type f -name "*.php" 2>/dev/null); then
        while IFS= read -r __file; do
            if [ -f "$__file" ] && [ -w "$__file" ]; then
                echo "    Processing: $(basename "$__file")"
                
                # Escape replacement strings for sed
                KEY_PREFIX_UPPER_REPL=$(escape_replacement "$KEY_PREFIX_UPPER")
                KEY_PREFIX_LOWER_REPL=$(escape_replacement "$KEY_PREFIX_LOWER")
                TEXTDOMAIN_REPL=$(escape_replacement "$TEXTDOMAIN")
                NEW_NAME_REPL=$(escape_replacement "$NEW_NAME")
                NEW_NAME_CAP_REPL=$(escape_replacement "$NEW_NAME_CAP")
                CLASS_PREFIX_FILE_REPL=$(escape_replacement "$CLASS_PREFIX_FILE")
                CLASS_PREFIX_NAMESPACE_REPL=$(escape_replacement "$CLASS_PREFIX_NAMESPACE")
                CLASS_PREFIX_CLASS_REPL=$(escape_replacement "$CLASS_PREFIX_CLASS")
                
                # Replace {{placeholders}} first
                # {{namespace}} -> CLASS_PREFIX_NAMESPACE (PascalCase with underscores preserved)
                sed_inplace "s|{{namespace}}|$CLASS_PREFIX_NAMESPACE_REPL|g" "$__file"
                # {{class_prefix}} in class name references (array( '{{class_prefix}}_Class' -> array( 'CLASS_PREFIX_Class')
                sed_inplace "s|array( '{{class_prefix}}_|array( '${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                sed_inplace "s|array( \"{{class_prefix}}_|array( \"${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                # {{class_prefix}} in class names ({{class_prefix}}_Updater_Init -> CLASS_PREFIX_Updater_Init)
                sed_inplace "s|{{class_prefix}}_\([A-Z][a-zA-Z_]*\)|${CLASS_PREFIX_CLASS_REPL}_\1|g" "$__file"
                # {{class_prefix}} in string literals (file paths, etc.) -> lowercase with dashes
                sed_inplace "s|'{{class_prefix}}|'$CLASS_PREFIX_FILE_REPL|g" "$__file"
                sed_inplace "s|\"{{class_prefix}}|\"$CLASS_PREFIX_FILE_REPL|g" "$__file"
                # {{class_prefix}} in other contexts -> lowercase with dashes (default)
                sed_inplace "s|{{class_prefix}}|$CLASS_PREFIX_FILE_REPL|g" "$__file"
                sed_inplace "s|{{textdomain}}|$TEXTDOMAIN_REPL|g" "$__file"
                # {{key_prefix}} in string values (for filter/meta/option names) -> lowercase, keep original format
                sed_inplace "s|{{key_prefix}}|$KEY_PREFIX_LOWER_REPL|g" "$__file"
                sed_inplace "s|{{plugin_slug}}|$NEW_NAME_REPL|g" "$__file"
                sed_inplace "s|{{plugin_name}}|$NEW_NAME_CAP_REPL|g" "$__file"
                
                # Replace namespace YMMVPL -> CLASS_PREFIX_NAMESPACE (PascalCase)
                sed_inplace "s|\bnamespace YMMVPL;|namespace $CLASS_PREFIX_NAMESPACE_REPL;|g" "$__file"
                
                # Replace class-ymmvpl references -> class-{class_prefix_file} (lowercase with dashes)
                sed_inplace "s|class-ymmvpl|class-$CLASS_PREFIX_FILE_REPL|g" "$__file"
                
                # Replace YMMVPL_ constants -> KEY_PREFIX_UPPER_ (uppercase, dash to underscore)
                # Replace in const definitions
                sed_inplace "s|\bconst YMMVPL_|const ${KEY_PREFIX_UPPER_REPL}_|g" "$__file"
                # Replace in define() calls
                sed_inplace "s|define( 'YMMVPL_|define( '${KEY_PREFIX_UPPER_REPL}_|g" "$__file"
                sed_inplace "s|define( \"YMMVPL_|define( \"${KEY_PREFIX_UPPER_REPL}_|g" "$__file"
                # Replace all YMMVPL_ constants in usage (must be before class name replacement)
                sed_inplace "s|\bYMMVPL_LICENSE_|${KEY_PREFIX_UPPER_REPL}_LICENSE_|g" "$__file"
                sed_inplace "s|\bYMMVPL_UPDATE_|${KEY_PREFIX_UPPER_REPL}_UPDATE_|g" "$__file"
                sed_inplace "s|\bYMMVPL_DEV_|${KEY_PREFIX_UPPER_REPL}_DEV_|g" "$__file"
                sed_inplace "s|\bYMMVPL_VERSION_|${KEY_PREFIX_UPPER_REPL}_VERSION_|g" "$__file"
                sed_inplace "s|\bYMMVPL_PLUGIN_|${KEY_PREFIX_UPPER_REPL}_PLUGIN_|g" "$__file"
                sed_inplace "s|\bYMMVPL_INCLUDES_|${KEY_PREFIX_UPPER_REPL}_INCLUDES_|g" "$__file"
                
                # Replace YMMVPL class names -> CLASS_PREFIX_CLASS (PascalCase)
                # Replace specific class name references first
                sed_inplace "s|\bYMMVPL_Init\b|${CLASS_PREFIX_CLASS_REPL}_Init|g" "$__file"
                sed_inplace "s|\bYMMVPL_Helpers\b|${CLASS_PREFIX_CLASS_REPL}_Helpers|g" "$__file"
                sed_inplace "s|\bYMMVPL_Notices\b|${CLASS_PREFIX_CLASS_REPL}_Notices|g" "$__file"
                sed_inplace "s|\bYMMVPL_Updater_Init\b|${CLASS_PREFIX_CLASS_REPL}_Updater_Init|g" "$__file"
                sed_inplace "s|\bYMMVPL_Updater\b|${CLASS_PREFIX_CLASS_REPL}_Updater|g" "$__file"
                sed_inplace "s|YMMVPL_Admin_Settings|${CLASS_PREFIX_CLASS_REPL}_Admin_Settings|g" "$__file"
                
                # Replace class definitions and common patterns
                sed_inplace "s|\bclass YMMVPL_|class ${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                sed_inplace "s|YMMVPL_::|${CLASS_PREFIX_CLASS_REPL}_::|g" "$__file"
                sed_inplace "s|new YMMVPL_|new ${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                sed_inplace "s|array( 'YMMVPL_|array( '${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                sed_inplace "s|array( \"YMMVPL_|array( \"${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                
                # Replace any remaining YMMVPL_ references (should be class names)
                sed_inplace "s|\bYMMVPL_|${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                
                # Replace ymmvpl (lowercase) references -> key_prefix_lower (for filter names, etc.)
                sed_inplace "s|\bymmvpl\b|$KEY_PREFIX_LOWER_REPL|g" "$__file"
                
            fi
        done <<< "$includes_files"
    else
        echo "  No PHP files found in includes/."
    fi
    
    # Replace placeholders and references in views/*.php files (including subdirectories)
    echo "  Replacing placeholders in views files..."
    if [ -d "./views" ]; then
        if views_files=$(find ./views -type f -name "*.php" 2>/dev/null); then
            while IFS= read -r __file; do
                if [ -f "$__file" ] && [ -w "$__file" ]; then
                    echo "    Processing: $(basename "$__file")"
                    
                    # Escape replacement strings for sed
                    KEY_PREFIX_UPPER_REPL=$(escape_replacement "$KEY_PREFIX_UPPER")
                    KEY_PREFIX_LOWER_REPL=$(escape_replacement "$KEY_PREFIX_LOWER")
                    TEXTDOMAIN_REPL=$(escape_replacement "$TEXTDOMAIN")
                    NEW_NAME_REPL=$(escape_replacement "$NEW_NAME")
                    NEW_NAME_CAP_REPL=$(escape_replacement "$NEW_NAME_CAP")
                    CLASS_PREFIX_FILE_REPL=$(escape_replacement "$CLASS_PREFIX_FILE")
                    CLASS_PREFIX_NAMESPACE_REPL=$(escape_replacement "$CLASS_PREFIX_NAMESPACE")
                    CLASS_PREFIX_CLASS_REPL=$(escape_replacement "$CLASS_PREFIX_CLASS")
                    
                    # Replace {{placeholders}} first
                    # {{namespace}} -> CLASS_PREFIX_NAMESPACE (PascalCase with underscores preserved)
                    sed_inplace "s|{{namespace}}|$CLASS_PREFIX_NAMESPACE_REPL|g" "$__file"
                    # {{class_prefix}} in class name references (array( '{{class_prefix}}_Class' -> array( 'CLASS_PREFIX_Class')
                    sed_inplace "s|array( '{{class_prefix}}_|array( '${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    sed_inplace "s|array( \"{{class_prefix}}_|array( \"${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    # {{class_prefix}} in class names ({{class_prefix}}_Updater_Init -> CLASS_PREFIX_Updater_Init)
                    sed_inplace "s|{{class_prefix}}_\([A-Z][a-zA-Z_]*\)|${CLASS_PREFIX_CLASS_REPL}_\1|g" "$__file"
                    # {{class_prefix}} in string literals (file paths, etc.) -> lowercase with dashes
                    sed_inplace "s|'{{class_prefix}}|'$CLASS_PREFIX_FILE_REPL|g" "$__file"
                    sed_inplace "s|\"{{class_prefix}}|\"$CLASS_PREFIX_FILE_REPL|g" "$__file"
                    # {{class_prefix}} in other contexts -> lowercase with dashes (default)
                    sed_inplace "s|{{class_prefix}}|$CLASS_PREFIX_FILE_REPL|g" "$__file"
                    sed_inplace "s|{{textdomain}}|$TEXTDOMAIN_REPL|g" "$__file"
                    # {{key_prefix}} in string values (for filter/meta/option names) -> lowercase, keep original format
                    sed_inplace "s|{{key_prefix}}|$KEY_PREFIX_LOWER_REPL|g" "$__file"
                    sed_inplace "s|{{plugin_slug}}|$NEW_NAME_REPL|g" "$__file"
                    sed_inplace "s|{{plugin_name}}|$NEW_NAME_CAP_REPL|g" "$__file"
                    
                    # Replace namespace YMMVPL -> CLASS_PREFIX_NAMESPACE (PascalCase)
                    sed_inplace "s|\bnamespace YMMVPL;|namespace $CLASS_PREFIX_NAMESPACE_REPL;|g" "$__file"
                    
                    # Replace class-ymmvpl references -> class-{class_prefix_file} (lowercase with dashes)
                    sed_inplace "s|class-ymmvpl|class-$CLASS_PREFIX_FILE_REPL|g" "$__file"
                    
                    # Replace YMMVPL_ constants -> KEY_PREFIX_UPPER_ (uppercase, dash to underscore)
                    sed_inplace "s|\bconst YMMVPL_|const ${KEY_PREFIX_UPPER_REPL}_|g" "$__file"
                    sed_inplace "s|define( 'YMMVPL_|define( '${KEY_PREFIX_UPPER_REPL}_|g" "$__file"
                    sed_inplace "s|define( \"YMMVPL_|define( \"${KEY_PREFIX_UPPER_REPL}_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_LICENSE_|${KEY_PREFIX_UPPER_REPL}_LICENSE_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_UPDATE_|${KEY_PREFIX_UPPER_REPL}_UPDATE_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_DEV_|${KEY_PREFIX_UPPER_REPL}_DEV_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_VERSION_|${KEY_PREFIX_UPPER_REPL}_VERSION_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_PLUGIN_|${KEY_PREFIX_UPPER_REPL}_PLUGIN_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_INCLUDES_|${KEY_PREFIX_UPPER_REPL}_INCLUDES_|g" "$__file"
                    
                    # Replace YMMVPL class names -> CLASS_PREFIX_CLASS (PascalCase)
                    sed_inplace "s|\bYMMVPL_Init\b|${CLASS_PREFIX_CLASS_REPL}_Init|g" "$__file"
                    sed_inplace "s|\bYMMVPL_Helpers\b|${CLASS_PREFIX_CLASS_REPL}_Helpers|g" "$__file"
                    sed_inplace "s|\bYMMVPL_Notices\b|${CLASS_PREFIX_CLASS_REPL}_Notices|g" "$__file"
                    sed_inplace "s|\bYMMVPL_Updater_Init\b|${CLASS_PREFIX_CLASS_REPL}_Updater_Init|g" "$__file"
                    sed_inplace "s|\bYMMVPL_Updater\b|${CLASS_PREFIX_CLASS_REPL}_Updater|g" "$__file"
                    sed_inplace "s|YMMVPL_Admin_Settings|${CLASS_PREFIX_CLASS_REPL}_Admin_Settings|g" "$__file"
                    sed_inplace "s|\bclass YMMVPL_|class ${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    sed_inplace "s|YMMVPL_::|${CLASS_PREFIX_CLASS_REPL}_::|g" "$__file"
                    sed_inplace "s|new YMMVPL_|new ${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    sed_inplace "s|array( 'YMMVPL_|array( '${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    sed_inplace "s|array( \"YMMVPL_|array( \"${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    sed_inplace "s|\bYMMVPL_|${CLASS_PREFIX_CLASS_REPL}_|g" "$__file"
                    
                    # Replace ymmvpl (lowercase) references -> key_prefix_lower (for filter names, etc.)
                    sed_inplace "s|\bymmvpl\b|$KEY_PREFIX_LOWER_REPL|g" "$__file"
                fi
            done <<< "$views_files"
        else
            echo "  No PHP files found in views/."
        fi
    else
        echo "  Views directory not found, skipping..."
    fi
fi

# If FREE version, process main file constants and remove includes/views folders
if [ "$PLUGIN_TYPE" = "FREE" ]; then
    echo ""
    echo "=== Processing main file for FREE version ==="
    
    # Find main plugin file (should be renamed already)
    MAIN_FILE=$(find . -maxdepth 1 -type f -name "$NEW_NAME.php" | head -1)
    if [ -z "$MAIN_FILE" ]; then
        # Fallback to plugin-name.php if not renamed yet
        MAIN_FILE="./plugin-name.php"
    fi
    
    if [ -f "$MAIN_FILE" ]; then
        echo "  Processing: $(basename "$MAIN_FILE")"
        
        # Remove everything from line 31 onwards (keep only header)
        # Use sed to delete from line 31 to end of file
        sed_inplace '31,$d' "$MAIN_FILE"
        
        echo "  ✓ Main file processed (header only, constants and includes removed for FREE version)"
    else
        echo "  Warning: Main plugin file not found"
    fi
    
    echo ""
    echo "=== Removing licensing folders (FREE version) ==="
    if [ -d "./includes" ]; then
        echo "  Deleting includes folder..."
        rm -rf "./includes" || echo "    Warning: Failed to delete includes folder"
        echo "  ✓ Includes folder removed"
    else
        echo "  Includes folder not found, skipping..."
    fi
    if [ -d "./views" ]; then
        echo "  Deleting views folder..."
        rm -rf "./views" || echo "    Warning: Failed to delete views folder"
        echo "  ✓ Views folder removed"
    else
        echo "  Views folder not found, skipping..."
    fi
fi

echo ""
echo "=== Renaming complete! ==="
echo "Now deleting the rename-plugin script and documentation..."

# Delete the documentation file
if [ -f "./RENAME_PLUGIN_GUIDE.md" ]; then
    rm -f "./RENAME_PLUGIN_GUIDE.md" || echo "  Warning: Failed to delete RENAME_PLUGIN_GUIDE.md"
    echo "  ✓ Documentation file removed"
fi

# Self-delete the script
rm -- "$0"
