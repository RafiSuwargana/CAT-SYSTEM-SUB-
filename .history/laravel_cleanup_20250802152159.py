#!/usr/bin/env python3
"""
Laravel Folder Cleanup Script
Clean up development and testing files inside cat_flask folder
"""

import os
import shutil
import sys
from pathlib import Path

def get_file_size(filepath):
    """Get file size in bytes"""
    try:
        return os.path.getsize(filepath)
    except:
        return 0

def get_dir_size(path):
    """Get directory size recursively"""
    total_size = 0
    try:
        for dirpath, dirnames, filenames in os.walk(path):
            for filename in filenames:
                filepath = os.path.join(dirpath, filename)
                try:
                    total_size += os.path.getsize(filepath)
                except:
                    pass
    except:
        pass
    return total_size

def main():
    print("🧹 LARAVEL FOLDER CLEANUP")
    print("=" * 50)
    print("This will clean up development and testing files")
    print("inside the cat_flask/ Laravel project folder")
    
    # Laravel project path
    laravel_path = Path("cat_flask")
    if not laravel_path.exists():
        print("❌ cat_flask folder not found!")
        return
    
    os.chdir(laravel_path)
    
    print("\n🔍 LARAVEL CLEANUP ANALYSIS")
    print("=" * 50)
    
    # Core Laravel files to KEEP (essential for Laravel)
    keep_files = {
        # Core Laravel
        '.editorconfig': 'Editor configuration',
        '.env': 'Environment config (IMPORTANT!)',
        '.env.example': 'Environment template',
        '.gitattributes': 'Git attributes',
        '.gitignore': 'Git ignore rules',
        'app': 'Laravel application (CORE)',
        'artisan': 'Laravel CLI tool (CORE)',
        'bootstrap': 'Laravel bootstrap (CORE)',
        'composer.json': 'PHP dependencies (CORE)',
        'composer.lock': 'Dependency lock file (CORE)',
        'config': 'Laravel configuration (CORE)',
        'database': 'Database files (CORE)',
        'public': 'Web assets (CORE)',
        'resources': 'Views and assets (CORE)',
        'routes': 'Laravel routes (CORE)',
        'storage': 'Laravel storage (CORE)',
        'vendor': 'Composer dependencies (CORE)',
        'package.json': 'Node.js dependencies',
        'vite.config.js': 'Vite configuration',
        'phpunit.xml': 'Testing configuration (keep for future)',
        'Parameter_Item_IST.csv': 'Item bank data'
    }
    
    # Files/folders to DELETE (development/testing artifacts)
    delete_items = {
        # Development/Testing files
        'cleanup_system.bat': 'Cleanup batch file',
        'FINAL_CLEANUP.md': 'Documentation file',
        'fresh_analysis.bat': 'Analysis batch file',
        'HOW_TO_RUN.md': 'Documentation file',
        'INSTALLATION.md': 'Documentation file',
        'interactive_test.php': 'Test script',
        'live_monitor.bat': 'Monitor batch file',
        'monitor_realtime.bat': 'Monitor batch file',
        'performance_analysis.bat': 'Performance batch file',
        'PERFORMANCE_MONITOR_DOCUMENTATION.md': 'Documentation file',
        'PERFORMANCE_MONITOR_README.md': 'Documentation file',
        'performance_test.bat': 'Performance batch file',
        'quick_start.bat': 'Quick start batch file',
        'README.md': 'Local readme (duplicate)',
        'realtime_log_monitor.php': 'Monitor script',
        'REALTIME_MONITORING_GUIDE.md': 'Documentation file',
        'reset_logs.bat': 'Log reset batch file',
        'SERVICE_CLEANUP_SUMMARY.md': 'Documentation file',
        'session_analyzer.php': 'Analysis script',
        'simple_debug.bat': 'Debug batch file',
        'simple_performance_test.php': 'Test script',
        'start_log_monitor.bat': 'Monitor batch file',
        'start_performance_test.bat': 'Test batch file',
        'test_performance.php': 'Test script',
        'test_performance_monitor.php': 'Test script',
        'usage_examples.php': 'Example script',
        'venv': 'Python virtual environment (wrong place)',
        '__pycache__': 'Python cache directory',
        '.history': 'VSCode history directory',
        '.git': 'Git repository (duplicate)',
        'tests': 'Test files (can be removed for production)'
    }
    
    # Analyze current files
    current_items = [item for item in os.listdir('.') if item not in ['.', '..']]
    
    keep_items = []
    delete_list = []
    manual_review = []
    
    for item in current_items:
        if item in keep_files:
            keep_items.append((item, keep_files[item]))
        elif item in delete_items:
            delete_list.append((item, delete_items[item]))
        else:
            manual_review.append(item)
    
    print(f"📂 CURRENT ITEMS (in cat_flask/): {len(current_items)}")
    
    # Display analysis
    if keep_items:
        print(f"✅ ITEMS TO KEEP ({len(keep_items)}):")
        print("-" * 30)
        for item, description in sorted(keep_items):
            print(f"✅ {item:<35} | {description}")
    
    if delete_list:
        print(f"\n❌ SAFE TO DELETE ({len(delete_list)}):")
        print("-" * 30)
        total_size = 0
        for item, description in sorted(delete_list):
            if os.path.isfile(item):
                size = get_file_size(item)
                print(f"❌ {item:<35} | {description} ({size:,} bytes)")
                total_size += size
            elif os.path.isdir(item):
                size = get_dir_size(item)
                print(f"❌ {item:<35} | {description} (directory, {size:,} bytes)")
                total_size += size
        
        print(f"\n💾 SPACE SAVINGS ESTIMATE:")
        print(f"Items to delete: {len(delete_list)}")
        print(f"Space to free: {total_size:,} bytes ({total_size/1024/1024:.1f} MB)")
    
    if manual_review:
        print(f"\n⚠️  MANUAL REVIEW ({len(manual_review)}):")
        print("-" * 30)
        for item in sorted(manual_review):
            print(f"❓ {item:<35} | Unknown item - needs manual check")
    
    if not delete_list:
        print("\n✅ No items to delete found!")
        return
    
    print(f"\n❓ What would you like to do?")
    print("1. Dry run (show what would be deleted)")
    print("2. Execute cleanup")
    print("3. Exit")
    
    try:
        choice = input("Enter choice (1-3): ").strip()
        
        if choice == "1":
            print(f"\n🔍 DRY RUN - These items would be deleted:")
            print("=" * 50)
            for item, description in sorted(delete_list):
                print(f"Would delete: {item} ({description})")
            
        elif choice == "2":
            print(f"\n⚠️  This will permanently delete {len(delete_list)} items!")
            confirm = input("Are you sure? (yes/no): ").strip().lower()
            
            if confirm == "yes":
                print(f"\n🗑️  EXECUTING LARAVEL CLEANUP")
                print("=" * 40)
                
                deleted_count = 0
                total_freed = 0
                
                for item, description in delete_list:
                    try:
                        if os.path.isfile(item):
                            size = get_file_size(item)
                            os.remove(item)
                            print(f"✅ Deleted file: {item} ({size:,} bytes)")
                            total_freed += size
                            deleted_count += 1
                        elif os.path.isdir(item):
                            size = get_dir_size(item)
                            shutil.rmtree(item)
                            print(f"✅ Deleted directory: {item}/ ({size:,} bytes)")
                            total_freed += size
                            deleted_count += 1
                    except Exception as e:
                        print(f"❌ Failed to delete {item}: {str(e)}")
                
                print(f"\n✅ Laravel cleanup completed!")
                print(f"Items cleaned: {deleted_count}")
                print(f"Space freed: {total_freed:,} bytes ({total_freed/1024/1024:.1f} MB)")
            else:
                print("Cleanup cancelled.")
                
        elif choice == "3":
            print("Exiting...")
            
        else:
            print("Invalid choice!")
            
    except KeyboardInterrupt:
        print("\nCleanup cancelled by user.")
    except Exception as e:
        print(f"Error: {str(e)}")

if __name__ == "__main__":
    main()
