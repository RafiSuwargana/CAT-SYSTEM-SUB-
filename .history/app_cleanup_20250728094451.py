#!/usr/bin/env python3
"""
Laravel App Cleanup Script
Clean up unused controllers and other files in cat_flask/app folder
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

def main():
    print("🧹 LARAVEL APP CLEANUP")
    print("=" * 50)
    print("This will clean up unused controllers and files")
    print("inside the cat_flask/app/ folder")
    
    # Laravel app path
    app_path = Path("cat_flask/app")
    if not app_path.exists():
        print("❌ cat_flask/app folder not found!")
        return
    
    print(f"\n🔍 LARAVEL APP CLEANUP ANALYSIS")
    print("=" * 50)
    
    # Files to DELETE (confirmed unused)
    delete_files = {
        'Http/Controllers/CatController.php': 'Unused controller (not in routes)',
        'Logging/CatLogFormatter.php': 'Custom log formatter (likely unused)',
        'Logging/CatPerformanceFormatter.php': 'Performance log formatter (likely unused)'
    }
    
    # Files to KEEP (essential/active)
    keep_files = {
        'Http/Controllers/Controller.php': 'Base controller (Laravel default)',
        'Http/Controllers/HybridCATController.php': 'Main CAT controller (ACTIVE)',
        'Http/Controllers/TestController.php': 'Test endpoints (ACTIVE)',
        'Http/Controllers/HomeController.php': 'Home controller (ACTIVE)',
        'Services/HybridCATService.php': 'Primary CAT service (CORE)',
        'Services/FlaskApiService.php': 'Flask API client (CORE)',
        'Services/PerformanceMonitorService.php': 'Performance monitoring (CORE)',
        'Services/CATService.php': 'Fallback CAT service (CORE)',
        'Models/TestSession.php': 'Database model (CORE)',
        'Models/TestResponse.php': 'Database model (CORE)',
        'Models/ItemParameter.php': 'Database model (CORE)',
        'Models/UsedItem.php': 'Database model (CORE)',
        'Models/User.php': 'Laravel default model (CORE)',
        'Providers/AppServiceProvider.php': 'Laravel service provider (CORE)'
    }
    
    # Check which files exist
    existing_delete_files = []
    existing_keep_files = []
    
    for file_path, description in delete_files.items():
        full_path = app_path / file_path
        if full_path.exists():
            size = get_file_size(full_path)
            existing_delete_files.append((file_path, description, size))
    
    for file_path, description in keep_files.items():
        full_path = app_path / file_path
        if full_path.exists():
            existing_keep_files.append((file_path, description))
    
    # Display analysis
    if existing_keep_files:
        print(f"✅ FILES TO KEEP ({len(existing_keep_files)}):")
        print("-" * 40)
        for file_path, description in existing_keep_files:
            print(f"✅ {file_path:<40} | {description}")
    
    if existing_delete_files:
        print(f"\n❌ FILES TO DELETE ({len(existing_delete_files)}):")
        print("-" * 40)
        total_size = 0
        for file_path, description, size in existing_delete_files:
            print(f"❌ {file_path:<40} | {description} ({size:,} bytes)")
            total_size += size
        
        print(f"\n💾 SPACE SAVINGS ESTIMATE:")
        print(f"Files to delete: {len(existing_delete_files)}")
        print(f"Space to free: {total_size:,} bytes ({total_size/1024:.1f} KB)")
    else:
        print("\n✅ No unused files found to delete!")
        return
    
    print(f"\n❓ What would you like to do?")
    print("1. Dry run (show what would be deleted)")
    print("2. Execute cleanup")
    print("3. Exit")
    
    try:
        choice = input("Enter choice (1-3): ").strip()
        
        if choice == "1":
            print(f"\n🔍 DRY RUN - These files would be deleted:")
            print("=" * 50)
            for file_path, description, size in existing_delete_files:
                full_path = app_path / file_path
                print(f"Would delete: {full_path} ({description})")
            
        elif choice == "2":
            print(f"\n⚠️  This will permanently delete {len(existing_delete_files)} files!")
            confirm = input("Are you sure? (yes/no): ").strip().lower()
            
            if confirm == "yes":
                print(f"\n🗑️  EXECUTING APP CLEANUP")
                print("=" * 40)
                
                deleted_count = 0
                total_freed = 0
                
                for file_path, description, size in existing_delete_files:
                    try:
                        full_path = app_path / file_path
                        os.remove(full_path)
                        print(f"✅ Deleted file: {file_path} ({size:,} bytes)")
                        total_freed += size
                        deleted_count += 1
                    except Exception as e:
                        print(f"❌ Failed to delete {file_path}: {str(e)}")
                
                print(f"\n✅ Laravel app cleanup completed!")
                print(f"Files deleted: {deleted_count}")
                print(f"Space freed: {total_freed:,} bytes ({total_freed/1024:.1f} KB)")
                
                # Check if empty directories need to be removed
                logging_dir = app_path / "Logging"
                if logging_dir.exists() and not any(logging_dir.iterdir()):
                    try:
                        os.rmdir(logging_dir)
                        print(f"✅ Removed empty directory: app/Logging/")
                    except:
                        pass
                        
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
