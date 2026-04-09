# PHireScript CLI Documentation
PHireScript is a powerful transpiler that converts .ps files into strictly typed PHP code. This guide covers how to initialize your project and use the built-in CLI tools to compile, debug, and monitor your source code.

## 1. Project Initialization
To start using PHireScript, you must first initialize your project configuration. This creates a PHireScript.json file in your root directory, which stores your environment settings and default paths.

Usage
```Bash

php bin/init
```
Interactive Setup
The command will guide you through a series of questions:

Development Mode: (y/n) Toggle specific debug features.

Application Namespace: The base prefix for all generated PHP classes.

Source Path: Default directory containing your .ps files (e.g., src/ps).

Distribution Path: Default directory for compiled .php files (e.g., dist/php).

## 2. Debugging Source Files
If you need to inspect how the internal engine (Lexer, Parser, Binder, and Emitter) is handling your code, use the debug command. This is essential for understanding the transformation of tokens into an Abstract Syntax Tree (AST).

Usage
```Bash

php bin/debug <source_file>
```
<source_file>: (Required) Path to the specific .ps file you wish to analyze.

Example
```Bash

php bin/debug src/User.ps
```
## 3. Building the Project
The build command compiles your PHireScript files into production-ready PHP files.

Usage
```Bash

php bin/build [origin_dir] [destiny_dir]
```
origin_dir: (Optional) Overrides the source path defined in PHireScript.json.

destiny_dir: (Optional) Overrides the distribution path defined in PHireScript.json.

Example
```Bash

# Uses paths from PHireScript.json
php bin/build
```

```Bash
# Overrides paths for a specific build
php bin/build ./modules/auth ./build/auth
```
## 4. Generating Snapshots (.pp files)
The snapshot command generates a Pre-Processed PHP file (.pp). This represents the intermediate state of your code: it is mostly PHP but still contains PHireScript-specific metadata before the final treatment (formatting and final emitter cleanup).

Usage
```Bash

php bin/snapshot [origin_dir] [destiny_dir]
```
Purpose
Use this to audit the logic conversion without the final PHP boilerplate or formatting being applied. It is the "raw" output of the transpilation engine.

## 5. File Watcher (Hot Reload)
The watch command starts a persistent process that monitors your source directory for changes. When a .ps file is saved, PHireScript re-compiles only that specific file, ensuring high performance during development.

Usage
```Bash

php bin/watch [origin_dir] [destiny_dir]
```
Controls
To stop: Press Ctrl + C in your terminal.

Example
```Bash

php bin/watch
# Output: [WATCHER] Monitoring src/...
# Output: [COMPILED] User.ps -> User.php (12ms)
```
Configuration Reference (PHireScript.json)
You can manually edit your configuration file at any time. A typical structure looks like this:

```JSON
{
  "dev": true,
  "namespace": "App",
  "paths": {
    "source": "src",
    "dist": "dist"
  },
  "generated_at": "2026-01-06 20:40:00"
}
```
