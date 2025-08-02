#!/usr/bin/env python3
"""
Script untuk membersihkan file-file development dan dokumentasi 
yang tidak diperlukan di luar folder cat_flask
"""

import os
import shutil
from pathlib import Path

class ExtraFileCleanup:
    def __init__(self, root_path="c:/Users/user/Documents/cat_flask"):
        self.root_path = Path(root_path)
        self.cleaned_files = []
        
    def analyze_extra_files(self):
        """Analisis file-file yang bisa dihapus"""
        
        # Files yang HARUS DIPERTAHANKAN
        keep_files = {
            'cat_api.py': 'Main Python API server',
            'Parameter_Item_IST.csv': 'Item bank data',
            'requirements.txt': 'Python dependencies',
            '.gitignore': 'Git configuration',
            'cat_flask': 'Laravel project folder',
            'start_hybrid_system.bat': 'ONE-CLICK LAUNCHER (important!)',
            'README.md': 'Main documentation (new)',
            'QUICK_START.md': 'Quick start guide (new)',
            'INSTALLATION_GUIDE.md': 'Installation guide (new)',
            'API_DOCUMENTATION.md': 'API documentation (new)',
            'DEPLOYMENT_GUIDE.md': 'Deployment guide (new)'
        }
        
        # Files yang BISA DIHAPUS
        delete_files = {
            # Documentation files (old)
            'CARA_RUNNING.md': 'Documentation file',
            'CAT_API_PERFORMANCE_INTEGRATION.md': 'Documentation file',
            'FLASK_API_DOCUMENTATION.md': 'Documentation file', 
            'INSTALLATION.md': 'Documentation file',
            'LARAVEL_INTEGRATION_GUIDE.md': 'Documentation file',
            'PERFORMANCE_INTEGRATION_COMPLETE.md': 'Documentation file',
            'PROJECT_SUMMARY.md': 'Documentation file',
            'README_VENV.md': 'Documentation file',
            'SUMMARY.md': 'Documentation file',
            
            # Log files (dapat di-regenerate, tapi user minta keep)
            # 'cat_api.log': 'Log file (can regenerate)',
            
            # Development/Debug files
            'debug_api.py': 'Debug script',
            'debug_flask.bat': 'Debug batch file',
            'restart_flask_api.bat': 'Utility batch file',
            'simple_restart.bat': 'Utility batch file', 
            # 'start_hybrid_system.bat': 'Utility batch file',  # USER MINTA KEEP!
            'test_performance_logging.py': 'Test script',
            'cleanup_duplications.py': 'Cleanup script (job done)',
            'cors_configuration.py': 'Configuration script',
            'setup.php': 'Setup script',
            
            # Cache/History directories
            '__pycache__': 'Python cache directory',
            '.history': 'VSCode history directory'
        }
        
        # Files untuk manual review
        manual_review = {
            'README.md': 'Main documentation (decide if you want to keep)'
        }
        
        return keep_files, delete_files, manual_review
    
    def show_file_analysis(self):
        """Tampilkan analisis file"""
        keep_files, delete_files, manual_review = self.analyze_extra_files()
        
        print("🔍 EXTRA FILE CLEANUP ANALYSIS")
        print("=" * 50)
        
        # Show current files
        current_files = [f.name for f in self.root_path.iterdir() if f.name != 'cat_flask']
        
        print(f"\n📂 CURRENT FILES (excluding cat_flask/):")
        print(f"Total files/folders: {len(current_files)}")
        
        safe_to_delete = []
        files_to_keep = []
        files_for_review = []
        
        for file_name in current_files:
            if file_name in keep_files:
                files_to_keep.append((file_name, keep_files[file_name]))
            elif file_name in delete_files:
                safe_to_delete.append((file_name, delete_files[file_name]))
            elif file_name in manual_review:
                files_for_review.append((file_name, manual_review[file_name]))
            else:
                files_for_review.append((file_name, 'Unknown file - needs manual check'))
        
        print(f"\n✅ FILES TO KEEP ({len(files_to_keep)}):")
        print("-" * 30)
        for file_name, reason in files_to_keep:
            print(f"✅ {file_name:<35} | {reason}")
        
        print(f"\n❌ SAFE TO DELETE ({len(safe_to_delete)}):")
        print("-" * 30)
        for file_name, reason in safe_to_delete:
            file_path = self.root_path / file_name
            if file_path.is_file():
                size = file_path.stat().st_size
                print(f"❌ {file_name:<35} | {reason} ({size:,} bytes)")
            else:
                print(f"❌ {file_name:<35} | {reason} (directory)")
        
        if files_for_review:
            print(f"\n⚠️  MANUAL REVIEW ({len(files_for_review)}):")
            print("-" * 30)
            for file_name, reason in files_for_review:
                print(f"❓ {file_name:<35} | {reason}")
        
        return safe_to_delete, files_for_review
    
    def calculate_space_savings(self, files_to_delete):
        """Hitung berapa space yang bisa dihemat"""
        total_size = 0
        file_count = 0
        
        for file_name, _ in files_to_delete:
            file_path = self.root_path / file_name
            if file_path.exists():
                if file_path.is_file():
                    total_size += file_path.stat().st_size
                    file_count += 1
                elif file_path.is_dir():
                    for sub_file in file_path.rglob('*'):
                        if sub_file.is_file():
                            total_size += sub_file.stat().st_size
                            file_count += 1
        
        return total_size, file_count
    
    def execute_cleanup(self, files_to_delete, dry_run=True):
        """Execute cleanup"""
        if dry_run:
            print(f"\n🧪 DRY RUN - No files will be deleted")
            print("=" * 40)
        else:
            print(f"\n🗑️  EXECUTING CLEANUP")
            print("=" * 40)
        
        for file_name, reason in files_to_delete:
            file_path = self.root_path / file_name
            if file_path.exists():
                if dry_run:
                    if file_path.is_file():
                        size = file_path.stat().st_size
                        print(f"Would delete file: {file_name} ({size:,} bytes)")
                    else:
                        print(f"Would delete directory: {file_name}/")
                else:
                    try:
                        if file_path.is_file():
                            size = file_path.stat().st_size
                            file_path.unlink()
                            print(f"✅ Deleted file: {file_name} ({size:,} bytes)")
                        else:
                            shutil.rmtree(file_path)
                            print(f"✅ Deleted directory: {file_name}/")
                        self.cleaned_files.append(file_name)
                    except Exception as e:
                        print(f"❌ Error deleting {file_name}: {e}")
            else:
                print(f"⚠️  File not found: {file_name}")

def main():
    print("🧹 EXTRA FILE CLEANUP")
    print("=" * 50)
    print("This will clean up development and documentation files")
    print("outside the main cat_flask/ Laravel project folder")
    print()
    
    cleanup = ExtraFileCleanup()
    
    # Analyze files
    safe_to_delete, files_for_review = cleanup.show_file_analysis()
    
    # Calculate space savings
    total_size, file_count = cleanup.calculate_space_savings(safe_to_delete)
    
    print(f"\n💾 SPACE SAVINGS ESTIMATE:")
    print(f"Files to delete: {file_count}")
    print(f"Space to free: {total_size:,} bytes ({total_size/1024/1024:.1f} MB)")
    
    if not safe_to_delete:
        print("\n✅ No files to delete. Directory is already clean!")
        return
    
    print(f"\n❓ What would you like to do?")
    print("1. Dry run (show what would be deleted)")
    print("2. Execute cleanup")
    print("3. Exit")
    
    choice = input("Enter choice (1-3): ").strip()
    
    if choice == "1":
        cleanup.execute_cleanup(safe_to_delete, dry_run=True)
    elif choice == "2":
        print(f"\n⚠️  This will permanently delete {len(safe_to_delete)} files/folders!")
        print("Files to keep: cat_api.py, Parameter_Item_IST.csv, requirements.txt, cat_flask/")
        confirm = input("Are you sure? (yes/no): ").strip().lower()
        if confirm == "yes":
            cleanup.execute_cleanup(safe_to_delete, dry_run=False)
            print(f"\n✅ Cleanup completed!")
            print(f"Files/folders cleaned: {len(cleanup.cleaned_files)}")
            print(f"Space freed: {total_size:,} bytes ({total_size/1024/1024:.1f} MB)")
        else:
            print("Cleanup cancelled.")
    else:
        print("Exiting...")

if __name__ == "__main__":
    main()
