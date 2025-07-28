#!/usr/bin/env python3
"""
Script untuk membersihkan duplikasi environment, database, dan konfigurasi
dalam sistem CAT Flask/Laravel
"""

import os
import shutil
import json
from pathlib import Path

class CATSystemCleanup:
    def __init__(self, root_path="c:/Users/user/Documents/cat_flask"):
        self.root_path = Path(root_path)
        self.main_laravel = self.root_path / "cat_flask"
        self.duplicate_files = []
        self.cleaned_files = []
        
    def analyze_duplications(self):
        """Analisis file-file yang terduplikasi"""
        print("🔍 ANALYZING DUPLICATIONS...")
        print("=" * 50)
        
        # File yang kemungkinan terduplikasi
        check_files = [
            '.env',
            '.env.example', 
            'composer.json',
            'composer.lock',
            'artisan',
            'Parameter_Item_IST.csv',
            'README.md',
            'package.json'
        ]
        
        # Directory yang kemungkinan terduplikasi
        check_dirs = [
            'app',
            'config', 
            'database',
            'vendor',
            'storage',
            'public',
            'resources',
            'routes',
            'bootstrap'
        ]
        
        duplicates_found = {}
        
        for file_name in check_files:
            root_file = self.root_path / file_name
            nested_file = self.main_laravel / file_name
            
            if root_file.exists() and nested_file.exists():
                duplicates_found[file_name] = {
                    'root': str(root_file),
                    'nested': str(nested_file),
                    'type': 'file'
                }
        
        for dir_name in check_dirs:
            root_dir = self.root_path / dir_name
            nested_dir = self.main_laravel / dir_name
            
            if root_dir.exists() and nested_dir.exists():
                duplicates_found[dir_name] = {
                    'root': str(root_dir),
                    'nested': str(nested_dir),
                    'type': 'directory'
                }
        
        return duplicates_found
    
    def compare_env_files(self):
        """Bandingkan file .env yang terduplikasi"""
        root_env = self.root_path / '.env'
        nested_env = self.main_laravel / '.env'
        
        comparison = {
            'root_exists': root_env.exists(),
            'nested_exists': nested_env.exists(),
            'content_diff': False,
            'recommendation': ''
        }
        
        if comparison['root_exists'] and comparison['nested_exists']:
            try:
                with open(root_env, 'r', encoding='utf-8') as f:
                    root_content = f.read().strip()
                with open(nested_env, 'r', encoding='utf-8') as f:
                    nested_content = f.read().strip()
                
                comparison['content_diff'] = root_content != nested_content
                comparison['root_size'] = len(root_content)
                comparison['nested_size'] = len(nested_content)
                
                if comparison['content_diff']:
                    comparison['recommendation'] = 'MERGE_MANUAL'
                else:
                    comparison['recommendation'] = 'DELETE_ROOT'
                    
            except Exception as e:
                comparison['error'] = str(e)
                comparison['recommendation'] = 'CHECK_MANUAL'
        
        return comparison
    
    def compare_composer_files(self):
        """Bandingkan file composer.json yang terduplikasi"""
        root_composer = self.root_path / 'composer.json'
        nested_composer = self.main_laravel / 'composer.json'
        
        comparison = {
            'root_exists': root_composer.exists(),
            'nested_exists': nested_composer.exists(),
            'content_diff': False,
            'recommendation': ''
        }
        
        if comparison['root_exists'] and comparison['nested_exists']:
            try:
                with open(root_composer, 'r', encoding='utf-8') as f:
                    root_data = json.load(f)
                with open(nested_composer, 'r', encoding='utf-8') as f:
                    nested_data = json.load(f)
                
                comparison['content_diff'] = root_data != nested_data
                comparison['root_name'] = root_data.get('name', 'unknown')
                comparison['nested_name'] = nested_data.get('name', 'unknown')
                
                if comparison['content_diff']:
                    comparison['recommendation'] = 'KEEP_NESTED'
                else:
                    comparison['recommendation'] = 'DELETE_ROOT'
                    
            except Exception as e:
                comparison['error'] = str(e)
                comparison['recommendation'] = 'CHECK_MANUAL'
        
        return comparison
    
    def show_file_comparison(self):
        """Tampilkan perbandingan file untuk membantu keputusan"""
        print("\n🔍 FILE COMPARISON ANALYSIS")
        print("=" * 50)
        
        # Compare .env files
        print("\n📄 ENVIRONMENT FILES:")
        root_env = self.root_path / '.env'
        nested_env = self.main_laravel / '.env'
        
        if root_env.exists():
            with open(root_env, 'r') as f:
                root_lines = len(f.readlines())
            print(f"Root .env: {root_lines} lines")
        
        if nested_env.exists():
            with open(nested_env, 'r') as f:
                nested_lines = len(f.readlines())
            print(f"Nested .env: {nested_lines} lines")
            
        # Check APP_KEY in both
        try:
            with open(root_env, 'r') as f:
                root_content = f.read()
            root_has_key = 'APP_KEY=' in root_content and len(root_content.split('APP_KEY=')[1].split('\n')[0].strip()) > 10
            
            with open(nested_env, 'r') as f:
                nested_content = f.read()
            nested_has_key = 'APP_KEY=' in nested_content and len(nested_content.split('APP_KEY=')[1].split('\n')[0].strip()) > 10
            
            print(f"Root .env has APP_KEY: {'✅' if root_has_key else '❌'}")
            print(f"Nested .env has APP_KEY: {'✅' if nested_has_key else '❌'}")
            print(f"RECOMMENDATION: Use {'NESTED' if nested_has_key else 'ROOT'} .env")
            
        except Exception as e:
            print(f"Error checking APP_KEY: {e}")
        
        # Compare database directories
        print(f"\n🗄️  DATABASE DIRECTORIES:")
        root_db = self.root_path / 'database'
        nested_db = self.main_laravel / 'database'
        
        if root_db.exists():
            root_db_files = list(root_db.rglob('*'))
            print(f"Root database/: {len(root_db_files)} files")
            
        if nested_db.exists():
            nested_db_files = list(nested_db.rglob('*'))
            print(f"Nested database/: {len(nested_db_files)} files")
            
            # Check for SQLite database
            sqlite_file = nested_db / 'database.sqlite'
            if sqlite_file.exists():
                size = sqlite_file.stat().st_size
                print(f"  ✅ Has database.sqlite ({size:,} bytes)")
                print(f"RECOMMENDATION: Use NESTED database/ (has actual database)")
        
        # Compare storage directories
        print(f"\n📁 STORAGE DIRECTORIES:")
        root_storage = self.root_path / 'storage'
        nested_storage = self.main_laravel / 'storage'
        
        if root_storage.exists():
            root_logs = list((root_storage / 'logs').glob('*.log')) if (root_storage / 'logs').exists() else []
            print(f"Root storage/logs: {len(root_logs)} log files")
            
        if nested_storage.exists():
            nested_logs = list((nested_storage / 'logs').glob('*.log')) if (nested_storage / 'logs').exists() else []
            print(f"Nested storage/logs: {len(nested_logs)} log files")
            
            # Check for important logs
            cat_log = nested_storage / 'logs' / 'cat.log'
            if cat_log.exists():
                size = cat_log.stat().st_size
                print(f"  ✅ Has cat.log ({size:,} bytes)")
                print(f"RECOMMENDATION: Use NESTED storage/ (has active logs)")

    def generate_cleanup_plan(self):
        """Generate rencana pembersihan"""
        duplicates = self.analyze_duplications()
        env_comparison = self.compare_env_files()
        composer_comparison = self.compare_composer_files()
        
        # Show detailed comparison first
        self.show_file_comparison()
        
        cleanup_plan = {
            'safe_to_delete': [],
            'manual_review': [],
            'merge_required': []
        }
        
        print("\n📋 CLEANUP ANALYSIS REPORT")
        print("=" * 50)
        
        # Environment files - prioritize nested (has APP_KEY)
        if env_comparison['root_exists'] and env_comparison['nested_exists']:
            # Always recommend keeping nested .env (more complete)
            cleanup_plan['safe_to_delete'].append({
                'path': str(self.root_path / '.env'),
                'reason': 'Nested .env is more complete (has APP_KEY and more config)'
            })
        
        # Also delete .env.example from root if exists
        root_env_example = self.root_path / '.env.example'
        if root_env_example.exists():
            cleanup_plan['safe_to_delete'].append({
                'path': str(root_env_example),
                'reason': 'Duplicate .env.example (keep nested version)'
            })
        
        # Composer files
        if composer_comparison['root_exists'] and composer_comparison['nested_exists']:
            cleanup_plan['safe_to_delete'].append({
                'path': str(self.root_path / 'composer.json'),
                'reason': 'Duplicate composer.json (Laravel should be in nested only)'
            })
            if (self.root_path / 'composer.lock').exists():
                cleanup_plan['safe_to_delete'].append({
                    'path': str(self.root_path / 'composer.lock'),
                    'reason': 'Corresponding to deleted composer.json'
                })
        
        # Laravel structure files yang aman dihapus dari root
        laravel_files_to_remove = ['artisan', 'package.json']
        for file_name in laravel_files_to_remove:
            root_file = self.root_path / file_name
            nested_file = self.main_laravel / file_name
            if root_file.exists() and nested_file.exists():
                cleanup_plan['safe_to_delete'].append({
                    'path': str(root_file),
                    'reason': f'Laravel {file_name} should be in nested directory only'
                })
        
        # Laravel directories yang aman dihapus dari root
        laravel_dirs_to_remove = ['app', 'config', 'vendor', 'bootstrap', 'public', 'resources', 'routes']
        for dir_name in laravel_dirs_to_remove:
            root_dir = self.root_path / dir_name
            nested_dir = self.main_laravel / dir_name
            if root_dir.exists() and nested_dir.exists():
                cleanup_plan['safe_to_delete'].append({
                    'path': str(root_dir),
                    'reason': f'Laravel {dir_name} should be in nested directory only'
                })
        
        # Database directory - nested has actual database
        root_db = self.root_path / 'database'
        nested_db = self.main_laravel / 'database'
        if root_db.exists() and nested_db.exists():
            sqlite_file = nested_db / 'database.sqlite'
            if sqlite_file.exists():
                cleanup_plan['safe_to_delete'].append({
                    'path': str(root_db),
                    'reason': 'Nested database/ has actual SQLite database, root is empty'
                })
            else:
                cleanup_plan['manual_review'].append({
                    'path': str(root_db),
                    'reason': 'Database directory - check for migrations/data before deleting'
                })
        
        # Storage directory - nested has active logs
        root_storage = self.root_path / 'storage'
        nested_storage = self.main_laravel / 'storage'
        if root_storage.exists() and nested_storage.exists():
            cat_log = nested_storage / 'logs' / 'cat.log'
            if cat_log.exists():
                cleanup_plan['safe_to_delete'].append({
                    'path': str(root_storage),
                    'reason': 'Nested storage/ has active logs, root storage is not used'
                })
            else:
                cleanup_plan['manual_review'].append({
                    'path': str(root_storage),
                    'reason': 'Storage directory - check for logs/uploads before deleting'
                })
        
        # Virtual environment - keep nested one
        root_venv = self.root_path / 'venv'
        nested_venv = self.main_laravel / 'venv'
        if root_venv.exists() and nested_venv.exists():
            cleanup_plan['manual_review'].append({
                'path': str(root_venv),
                'reason': 'Python venv - check which one is active before deleting'
            })
        
        return cleanup_plan
    
    def print_cleanup_plan(self, plan):
        """Print rencana pembersihan"""
        print("\n🟢 SAFE TO DELETE:")
        print("-" * 30)
        for item in plan['safe_to_delete']:
            print(f"❌ {item['path']}")
            print(f"   Reason: {item['reason']}")
        
        print(f"\n🟡 MANUAL REVIEW REQUIRED:")
        print("-" * 30)
        for item in plan['manual_review']:
            print(f"⚠️  {item['path']}")
            print(f"   Reason: {item['reason']}")
        
        print(f"\n🔴 MERGE REQUIRED:")
        print("-" * 30)
        for item in plan['merge_required']:
            print(f"🔄 {item['file']}")
            print(f"   Root: {item['root_path']}")
            print(f"   Nested: {item['nested_path']}")
            print(f"   Reason: {item['reason']}")
    
    def execute_safe_cleanup(self, plan, dry_run=True):
        """Execute safe cleanup operations"""
        if dry_run:
            print(f"\n🧪 DRY RUN - No files will be deleted")
            print("=" * 40)
        else:
            print(f"\n🗑️  EXECUTING CLEANUP")
            print("=" * 40)
        
        for item in plan['safe_to_delete']:
            path = Path(item['path'])
            if path.exists():
                if dry_run:
                    if path.is_file():
                        print(f"Would delete file: {path}")
                    else:
                        print(f"Would delete directory: {path}")
                else:
                    try:
                        if path.is_file():
                            path.unlink()
                            print(f"✅ Deleted file: {path}")
                        else:
                            shutil.rmtree(path)
                            print(f"✅ Deleted directory: {path}")
                        self.cleaned_files.append(str(path))
                    except Exception as e:
                        print(f"❌ Error deleting {path}: {e}")
            else:
                print(f"⚠️  File not found: {path}")

def main():
    print("🧹 CAT SYSTEM DUPLICATION CLEANUP")
    print("=" * 50)
    
    cleanup = CATSystemCleanup()
    
    # Generate cleanup plan
    plan = cleanup.generate_cleanup_plan()
    
    # Print plan
    cleanup.print_cleanup_plan(plan)
    
    print(f"\n📊 SUMMARY:")
    print(f"Files safe to delete: {len(plan['safe_to_delete'])}")
    print(f"Files needing manual review: {len(plan['manual_review'])}")
    print(f"Files needing merge: {len(plan['merge_required'])}")
    
    # Ask for confirmation
    print(f"\n❓ What would you like to do?")
    print("1. Dry run (show what would be deleted)")
    print("2. Execute safe cleanup")
    print("3. Exit")
    
    choice = input("Enter choice (1-3): ").strip()
    
    if choice == "1":
        cleanup.execute_safe_cleanup(plan, dry_run=True)
    elif choice == "2":
        confirm = input("Are you sure? This will permanently delete files! (yes/no): ").strip().lower()
        if confirm == "yes":
            cleanup.execute_safe_cleanup(plan, dry_run=False)
            print(f"\n✅ Cleanup completed!")
            print(f"Files cleaned: {len(cleanup.cleaned_files)}")
        else:
            print("Cleanup cancelled.")
    else:
        print("Exiting...")

if __name__ == "__main__":
    main()
