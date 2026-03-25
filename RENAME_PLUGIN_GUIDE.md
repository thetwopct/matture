# WordPress Plugin Renamer Usage Guide

## Overview

`rename-plugin.sh` is an automated script for renaming WordPress plugins. It renames `plugin-name` to your specified new plugin name and handles all related files, directories, and code references.

## Prerequisites

- Bash shell (Linux/macOS/WSL)
- Script needs executable permissions

## Usage

### 1. Grant Execute Permission

```bash
chmod +x rename-plugin.sh
```

### 2. Run the Script

```bash
./rename-plugin.sh
```

### 3. Enter Information as Prompted

The script will ask for the following information in sequence:

#### Basic Information (All Versions)

1. **New Plugin Name (slug format)**
   - Format: Lowercase letters, numbers, and hyphens only
   - Example: `my-awesome-plugin`
   - Validation rule: `^[a-z0-9]+(-[a-z0-9]+)*$`

2. **New Plugin Name (readable format)**
   - Format: Any readable text
   - Example: `My Awesome Plugin`

3. **Plugin Type**
   - Options: `FREE` or `PRO`
   - Default: `FREE` (press Enter to use default)
   - Case insensitive (automatically converted to uppercase)

#### PRO Version Additional Information

If you select `PRO` version, you also need to enter:

4. **Key Prefix (required)**
   - Purpose: Used for constant names, filter names, meta keys, etc.
   - Format: Letters, numbers, underscores, and hyphens allowed
   - Example: `my_plugin` or `my-plugin`
   - **Case Sensitive Rules**:
     - For constant names: Automatically converted to **uppercase**, hyphens converted to underscores
     - For filter/meta/option: Automatically converted to **lowercase**, keeping original format

5. **Textdomain (optional)**
   - Default: Uses plugin slug (`NEW_NAME`)
   - Purpose: WordPress internationalization text domain
   - Example: Press Enter to use default, or enter custom value

6. **Class Prefix (optional)**
   - Default: Uses Key Prefix value
   - Purpose: Class names, namespaces, file names
   - Format: Letters, numbers, underscores allowed
   - **Case Sensitive Rules**:
     - For class names/namespaces: **PascalCase**, **preserves underscores**
     - For file names: **lowercase**, underscores converted to hyphens

### 4. Confirm Information

The script will display a summary of all entered information. Enter `y` to proceed after verification.

## Execution Flow Details

### Phase 1: Basic Renaming (All Versions)

#### Step 1: Rename Files and Directories

**Execution:**
- Scans all files and directories containing `plugin-name`
- Excludes `.git` directory and `rename-plugin.sh` script itself
- Replaces `plugin-name` in matching file/directory names with new plugin slug

**Examples:**
```
plugin-name.php → my-awesome-plugin.php
plugin-name/ → my-awesome-plugin/
```

#### Step 2: Replace Text Content

**Execution:**
- Finds and replaces `plugin-name` → `NEW_NAME` in all files
- Finds and replaces `PluginName` → `NEW_NAME_CAP` in all files
- Excludes `.git` directory and `rename-plugin.sh` script

**File Types Processed:**
- PHP files
- JavaScript files
- CSS files
- JSON files
- Configuration files
- Other text files

#### Step 3: Verification

**Execution:**
- Checks for any remaining references to `plugin-name` or `PluginName`
- If found, displays first 5 matches for manual review

### Phase 2: PRO Version Special Processing

If `PRO` version is selected, the script performs the following additional processing:

#### 2.1 Process Main File (plugin-name.php)

**Execution:**

1. **Replace {{key_prefix}} in constant definitions**
   ```php
   // Before
   define( '{{key_prefix}}_PLUGIN_FILE', __FILE__ );
   
   // After (assuming key_prefix = "my_plugin")
   define( 'MY_PLUGIN_PLUGIN_FILE', __FILE__ );
   ```

2. **Replace {{textdomain}}**
   ```php
   // Before
   define( 'MY_PLUGIN_PLUGIN_SLUG', '{{textdomain}}' );
   
   // After (assuming textdomain = "my-plugin")
   define( 'MY_PLUGIN_PLUGIN_SLUG', 'my-plugin' );
   ```

3. **Replace __FILE__ references**
   ```php
   // Before
   plugin_dir_path( __FILE__ )
   
   // After
   plugin_dir_path( MY_PLUGIN_PLUGIN_FILE )
   ```

4. **Replace YMMVPL constants**
   ```php
   // Before
   require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-init.php';
   
   // After
   require_once MY_PLUGIN_INCLUDES_PATH . 'class-my-plugin-init.php';
   ```

5. **Replace namespace and class references**
   ```php
   // Before
   \YMMVPL\YMMVPL_Init::init();
   
   // After (assuming class_prefix = "my_plugin")
   \My_Plugin\My_Plugin_Init::init();
   ```

#### 2.2 Rename Class Files in includes/ Directory

**Execution:**
- Finds all `class-ymmvpl*.php` files
- Renames to `class-{class_prefix_file}*.php`

**Examples:**
```
class-ymmvpl-init.php → class-my-plugin-init.php
class-ymmvpl-helpers.php → class-my-plugin-helpers.php
```

#### 2.3 Replace Content in includes/ Directory

**Execution:**

1. **Replace {{placeholders}}**
   - `{{namespace}}` → `CLASS_PREFIX_NAMESPACE` (PascalCase, preserves underscores)
   - `{{class_prefix}}` → Context-dependent:
     - Class name references: `CLASS_PREFIX_CLASS` (PascalCase, preserves underscores)
     - File paths: `CLASS_PREFIX_FILE` (lowercase, underscores to hyphens)
   - `{{textdomain}}` → `TEXTDOMAIN`
   - `{{key_prefix}}` → `KEY_PREFIX_LOWER` (lowercase, keeps original format)
   - `{{plugin_slug}}` → `NEW_NAME`
   - `{{plugin_name}}` → `NEW_NAME_CAP`

2. **Replace namespace**
   ```php
   // Before
   namespace YMMVPL;
   
   // After
   namespace My_Plugin;
   ```

3. **Replace YMMVPL_ constants**
   ```php
   // Before
   const YMMVPL_LICENSE_KEY = '{{key_prefix}}_license_key';
   
   // After
   const MY_PLUGIN_LICENSE_KEY = 'my_plugin_license_key';
   ```

4. **Replace YMMVPL class names**
   ```php
   // Before
   class YMMVPL_Init {
       new YMMVPL_Helpers();
   }
   
   // After
   class My_Plugin_Init {
       new My_Plugin_Helpers();
   }
   ```

5. **Replace lowercase ymmvpl references**
   ```php
   // Before
   wp_nonce_field( 'ymmvpl_activate_license_action', 'ymmvpl_activate_license_nonce' );
   
   // After
   wp_nonce_field( 'my_plugin_activate_license_action', 'my_plugin_activate_license_nonce' );
   ```

#### 2.4 Replace Content in views/ Directory

**Execution:** Same replacement logic as `includes/` directory

### Phase 3: FREE Version Processing

If `FREE` version is selected:

#### 3.1 Process Main File

**Execution:**
- Deletes all content after line 31 in main file (keeps plugin header comment)
- Removes all constant definitions and `require_once` statements

#### 3.2 Delete Folders

**Execution:**
- Deletes `includes/` directory (if exists)
- Deletes `views/` directory (if exists)

### Phase 4: Cleanup

**Execution:**
- Automatically deletes `rename-plugin.sh` script itself
- Automatically deletes `RENAME_PLUGIN_GUIDE.md` documentation

## Case Sensitive Rules Explained

### Key Prefix Conversion Rules

| Use Case | Conversion Rule | Examples |
|---------|----------------|----------|
| **Constant Names** | Uppercase + hyphens to underscores | `my_plugin` → `MY_PLUGIN`<br>`my-plugin` → `MY_PLUGIN` |
| **Filter/Meta/Option Names** | Lowercase + keep original format | `my_plugin` → `my_plugin`<br>`my-plugin` → `my-plugin` |

**Example:**
```php
// Input: key_prefix = "my_plugin"

// Constant definition
define( 'MY_PLUGIN_LICENSE_KEY', 'my_plugin_license_key' );
//              ↑ uppercase                  ↑ lowercase keeps original

// Filter name
apply_filters( 'my_plugin_custom_filter', $value );
//              ↑ lowercase keeps original
```

### Class Prefix Conversion Rules

| Use Case | Conversion Rule | Examples |
|---------|----------------|----------|
| **Class Names/Namespaces** | PascalCase + preserves underscores | `my_plugin` → `My_Plugin`<br>`my_awesome_plugin` → `My_Awesome_Plugin` |
| **File Names** | Lowercase + underscores to hyphens | `my_plugin` → `my-plugin`<br>`my_awesome_plugin` → `my-awesome-plugin` |

**Example:**
```php
// Input: class_prefix = "my_plugin"

// Class name and namespace
namespace My_Plugin;
class My_Plugin_Init {
    // Preserves underscores, first letter of each word capitalized
}

// File name
class-my-plugin-init.php
// Underscores converted to hyphens, all lowercase
```

### Conversion Logic Explanation

#### 1. Key Prefix → Constant Name (Uppercase)

```bash
# Conversion logic
KEY_PREFIX_UPPER = $(echo "$KEY_PREFIX" | tr '[:lower:]' '[:upper:]' | tr '-' '_')
```

**Conversion Steps:**
1. All letters converted to uppercase
2. Hyphens converted to underscores

**Examples:**
- `my_plugin` → `MY_PLUGIN`
- `my-plugin` → `MY_PLUGIN`
- `My_Plugin` → `MY_PLUGIN`

#### 2. Key Prefix → Filter/Meta (Lowercase)

```bash
# Conversion logic
KEY_PREFIX_LOWER = $(echo "$KEY_PREFIX" | tr '[:upper:]' '[:lower:]')
```

**Conversion Steps:**
1. All letters converted to lowercase
2. **Keep original format** (do not convert hyphens or underscores)

**Examples:**
- `my_plugin` → `my_plugin`
- `my-plugin` → `my-plugin`
- `My_Plugin` → `my_plugin`

#### 3. Class Prefix → Class Name/Namespace (PascalCase)

```bash
# Conversion logic
CLASS_PREFIX_CLASS = $(echo "$CLASS_PREFIX" | awk -F'_' '{
    for(i=1;i<=NF;i++)
        if(length($i)>0)
            $i=toupper(substr($i,1,1))tolower(substr($i,2))
    print
}' OFS='_')
```

**Conversion Steps:**
1. Split by underscores
2. Capitalize first letter of each part, lowercase the rest
3. Rejoin with underscores (**preserves underscores**)

**Examples:**
- `my_plugin` → `My_Plugin`
- `my_awesome_plugin` → `My_Awesome_Plugin`
- `plugin_name` → `Plugin_Name`

#### 4. Class Prefix → File Name (Lowercase + Hyphens)

```bash
# Conversion logic
CLASS_PREFIX_FILE = $(echo "$CLASS_PREFIX" | tr '_' '-' | tr '[:upper:]' '[:lower:]')
```

**Conversion Steps:**
1. Underscores converted to hyphens
2. All letters converted to lowercase

**Examples:**
- `my_plugin` → `my-plugin`
- `my_awesome_plugin` → `my-awesome-plugin`
- `Plugin_Name` → `plugin-name`

## Complete Example

### Input Information

```
New plugin slug: my-awesome-plugin
New plugin name: My Awesome Plugin
Plugin type: PRO
Key Prefix: my_plugin
Textdomain: my-awesome-plugin (default)
Class Prefix: my_plugin (default)
```

### Conversion Results

#### Main File (my-awesome-plugin.php)

```php
// Constant definitions
define( 'MY_PLUGIN_PLUGIN_FILE', __FILE__ );
define( 'MY_PLUGIN_PLUGIN_SLUG', 'my-awesome-plugin' );
define( 'MY_PLUGIN_INCLUDES_PATH', plugin_dir_path( MY_PLUGIN_PLUGIN_FILE ) . 'includes/' );

// Class references
require_once MY_PLUGIN_INCLUDES_PATH . 'class-my-plugin-init.php';
\My_Plugin\My_Plugin_Init::init();
```

#### includes/ Directory

**File Renaming:**
- `class-ymmvpl-init.php` → `class-my-plugin-init.php`

**Content Replacement:**
```php
// Namespace
namespace My_Plugin;

// Class name
class My_Plugin_Init {
    // ...
}

// Constants
const MY_PLUGIN_LICENSE_KEY = 'my_plugin_license_key';

// Filter names
apply_filters( 'my_plugin_custom_filter', $value );

// Class references
array( 'My_Plugin\My_Plugin_Updater_Init', 'init' )
```

## Important Notes

1. **Backup Important Data**: The script modifies files directly. It's recommended to commit to Git or create a backup before running

2. **Verify Remaining References**: The script checks for remaining old references, but manual review is recommended

3. **Script Self-Deletion**: The script automatically deletes itself upon completion

4. **Case Sensitivity**:
   - Constant names: Must be uppercase
   - Class names: PascalCase, preserves underscores
   - File names: Lowercase, underscores to hyphens
   - Filter/Meta: Lowercase, keeps original format

5. **Underscore Preservation Rules**:
   - Class names and namespaces: **Preserves underscores**
   - File names: Underscores converted to hyphens

6. **Hyphen Handling**:
   - Key Prefix in constants: Hyphens converted to underscores
   - Key Prefix in filter/meta: Keeps original
   - Class Prefix in file names: Underscores converted to hyphens

## Troubleshooting

### Issue: Script Reports Permission Error

**Solution:**
```bash
chmod +x rename-plugin.sh
```

### Issue: Remaining References Found

**Solution:**
1. Check the warning messages in script output
2. Manually review listed files
3. Use text editor or grep to search and replace

### Issue: File Name Conversion Error

**Solution:**
1. Check if input values meet format requirements
2. Ensure slug format is correct (lowercase letters, numbers, hyphens)
3. Re-run the script

## Verification Checklist

After running the script, it's recommended to check the following:

- [ ] All files have been renamed correctly
- [ ] Constant definitions in main file are correct
- [ ] Namespaces and class names have been replaced correctly
- [ ] Files in includes/ directory have been renamed and replaced correctly
- [ ] Content in views/ directory (if exists) has been replaced correctly
- [ ] No remaining references to `plugin-name` or `YMMVPL`
- [ ] Constant names use uppercase format
- [ ] Class names use PascalCase and preserve underscores
- [ ] Filter/Meta names use lowercase and keep original format
