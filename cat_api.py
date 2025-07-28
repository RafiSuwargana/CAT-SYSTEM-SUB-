#!/usr/bin/env python3
"""
Flask API Server for CAT System
IRT 3PL Calculations dengan EAP theta estimation dan EFI item selection
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import math
import logging
import os
import psutil
from datetime import datetime
import json
from scipy.stats import norm

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Performance Monitor Functions
def get_memory_usage():
    """Mendapatkan penggunaan memory dalam MB"""
    try:
        process = psutil.Process()
        memory_bytes = process.memory_info().rss
        memory_mb = memory_bytes / 1024 / 1024
        return f"{memory_mb:.1f}"
    except:
        # Fallback jika psutil tidak tersedia
        import sys
        if hasattr(sys, 'getsizeof'):
            memory_bytes = sys.getsizeof(globals())
            memory_mb = memory_bytes / 1024 / 1024
            return f"{memory_mb:.1f}"
        return "0.0"

def get_cpu_load():
    """Mendapatkan CPU load average"""
    try:
        # Untuk sistem yang mendukung psutil
        cpu_percent = psutil.cpu_percent(interval=0.1)
        return f"{cpu_percent/100:.2f}"
    except:
        # Fallback sederhana
        import time
        import random
        # Estimasi berdasarkan waktu dan random untuk simulasi
        base_load = 0.5 + (random.randint(-20, 20) / 100)
        return f"{max(0.1, min(2.0, base_load)):.2f}"

def log_process_performance(process_name):
    """Log proses dengan monitoring memory dan CPU usage"""
    try:
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        memory_usage = get_memory_usage()
        cpu_load = get_cpu_load()
        
        log_message = f"[{timestamp}] process: {process_name} | memory: {memory_usage}MB | cpu_load: {cpu_load}"
        
        # Log ke file cat.log
        log_file = 'cat_api.log'
        with open(log_file, 'a', encoding='utf-8') as f:
            f.write(log_message + '\n')
        
        # Log ke console juga
        logger.info(f"PERFORMANCE: {log_message}")
        
    except Exception as e:
        logger.error(f"Error logging performance: {str(e)}")

def log_start_cat():
    """Log mulai proses CAT"""
    log_process_performance('start_CAT')

def log_select_next_item():
    """Log pemilihan item berikutnya"""
    log_process_performance('select_next_item')

def log_estimate_theta_map():
    """Log estimasi theta MAP"""
    log_process_performance('estimate_theta_MAP')

def log_estimate_theta_eap():
    """Log estimasi theta EAP"""
    log_process_performance('estimate_theta_EAP')

def log_calculate_score():
    """Log perhitungan skor"""
    log_process_performance('calculate_score')

def log_stopping_criteria():
    """Log pengecekan kriteria berhenti"""
    log_process_performance('check_stopping_criteria')

def log_end_cat():
    """Log akhir proses CAT"""
    log_process_performance('end_CAT')

def log_item_selection():
    """Log proses seleksi item"""
    log_process_performance('item_selection')

def log_api_request(endpoint_name):
    """Log API request"""
    log_process_performance(f'api_request_{endpoint_name}')

def log_final_scoring():
    """Log proses final scoring"""
    log_process_performance('final_scoring')

app = Flask(__name__)

# CORS Configuration dengan filterisasi untuk keamanan
CORS(app, 
     origins=[
         'http://localhost:8000',     # Laravel development
         'http://127.0.0.1:8000',     # Alternative localhost
         'http://localhost:3000',     # React development (jika ada)
         'https://yourapp.com',       # Production domain (ganti dengan domain asli)
         'https://www.yourapp.com'    # Production www domain
     ],
     methods=['GET', 'POST', 'OPTIONS'],
     allow_headers=['Content-Type', 'Authorization', 'X-Requested-With'],
     supports_credentials=False,  # Set True jika butuh cookies/auth
     max_age=3600  # Cache preflight response for 1 hour
)

# Configuration
API_VERSION = "1.0.0"
PORT = 5000
HOST = "127.0.0.1"

# Load item parameters from CSV file
import pandas as pd

try:
    # Load from CSV file
    item_df = pd.read_csv('Parameter_Item_IST.csv')
    
    # Ensure required columns exist
    if 'ID' in item_df.columns:
        item_df = item_df.rename(columns={'ID': 'id'})
    
    # Convert to list of dictionaries
    ITEM_BANK = []
    for _, row in item_df.iterrows():
        ITEM_BANK.append({
            'id': str(row['id']),  # Keep as string for consistency
            'a': float(row['a']),
            'b': float(row['b']),
            'g': float(row['g']),
            'u': float(row.get('u', 1.0))  # Default u=1 if not in CSV
        })
    
    logger.info(f"✓ Loaded {len(ITEM_BANK)} items from Parameter_Item_IST.csv")
    
except FileNotFoundError:
    logger.error("✗ Parameter_Item_IST.csv not found! Please ensure the file exists.")
    ITEM_BANK = []
    exit(1)
except Exception as e:
    logger.error(f"✗ Error loading item parameters: {str(e)}")
    ITEM_BANK = []
    exit(1)

# IRT 3PL Functions
def probability_3pl(theta, a, b, g, u=1.0):
    """Fungsi probabilitas respons benar menggunakan model 3PL"""
    try:
        return g + (u - g) / (1 + np.exp(-a * (theta - b)))
    except (OverflowError, ValueError):
        return g if theta < b else u

def information_3pl(theta, a, b, g, u=1.0):
    """Calculate Fisher Information for 3PL model"""
    try:
        p = probability_3pl(theta, a, b, g, u)
        q = 1 - p
        
        if p <= g or p >= u or q <= 0:
            return 0.0
            
        numerator = (a**2) * (p - g)**2 * q
        denominator = p * (u - g)**2
        
        return numerator / denominator if denominator > 0 else 0.0
    except (OverflowError, ValueError, ZeroDivisionError):
        return 0.0

def likelihood_3pl(theta, responses):
    """Calculate likelihood for given theta and responses"""
    try:
        likelihood = 1.0
        for resp in responses:
            a, b, g = resp['a'], resp['b'], resp['g']
            u = resp.get('u', 1.0)  # Get u parameter or default to 1.0
            answer = resp['answer']
            
            p = probability_3pl(theta, a, b, g, u)
            
            if answer == 1:
                likelihood *= p
            else:
                likelihood *= (1 - p)
                
        return likelihood
    except (OverflowError, ValueError):
        return 0.0

def estimate_theta_map(responses, prior_mean=0.0, prior_sd=2.0, theta_old=0.0):
    """Estimate theta using MAP (Maximum A Posteriori) method for real-time estimation"""
    log_estimate_theta_map()  # Log performance
    try:
        if not responses:
            return prior_mean, prior_sd

        # Determine max allowed change based on number of responses
        if len(responses) <= 5:
            max_allowed_change = 1.0
        else:
            max_allowed_change = 0.25

        # Quadrature points and weights (expanded range and resolution)
        theta_range = np.linspace(-6, 6, 1001)
        # Prior distribution: N(0,2)
        weights = norm.pdf(theta_range, 0, 2)
        weights = weights / np.sum(weights)

        # Calculate likelihood for each theta
        likelihood = np.ones_like(theta_range)
        for resp in responses:
            a, b, g = resp['a'], resp['b'], resp['g']
            u = resp.get('u', 1.0)
            answer = resp['answer']
            p = np.array([probability_3pl(theta_val, a, b, g, u) for theta_val in theta_range])
            p = np.clip(p, 1e-10, 1 - 1e-10)
            if answer == 1:
                likelihood *= p
            else:
                likelihood *= (1 - p)

        # Calculate posterior
        posterior = likelihood * weights
        posterior_sum = np.sum(posterior)
        if posterior_sum > 0:
            posterior = posterior / posterior_sum
        else:
            # Fallback jika posterior sum = 0
            posterior = weights / np.sum(weights)

        # MAP estimate: argmax of posterior distribution
        theta_map_idx = np.argmax(posterior)
        theta_map = theta_range[theta_map_idx]

        # Apply max allowed change constraint
        total_change = theta_map - theta_old
        if abs(total_change) > max_allowed_change:
            direction = 1 if total_change > 0 else -1
            theta_map = theta_old + direction * max_allowed_change

        # Absolute theta bounds
        theta_map = max(-6, min(6, theta_map))

        # Calculate approximate SE using curvature around MAP estimate
        # Find the closest index to the constrained theta_map
        closest_idx = np.argmin(np.abs(theta_range - theta_map))
        
        # Calculate SE as inverse of Fisher Information at MAP
        se_map = 1.0  # Default SE
        try:
            # Calculate Fisher Information at MAP estimate
            fisher_info = 0.0
            for resp in responses:
                a, b, g = resp['a'], resp['b'], resp['g']
                u = resp.get('u', 1.0)
                info = information_3pl(theta_map, a, b, g, u)
                fisher_info += info
            
            if fisher_info > 0:
                se_map = 1.0 / np.sqrt(fisher_info)
            else:
                se_map = 1.0
        except:
            se_map = 1.0

        return theta_map, se_map
    except (OverflowError, ValueError, ZeroDivisionError):
        return prior_mean, prior_sd

def estimate_theta_eap(responses, prior_mean=0.0, prior_sd=2.0):
    """Estimate theta using EAP (Expected A Posteriori) method for final scoring"""
    log_estimate_theta_eap()  # Log performance
    try:
        if not responses:
            return prior_mean, prior_sd

        # Quadrature points and weights (expanded range and resolution)
        theta_range = np.linspace(-6, 6, 1001)
        # Prior distribution: N(0,2)
        weights = norm.pdf(theta_range, 0, 2)
        weights = weights / np.sum(weights)

        # Calculate likelihood for each theta
        likelihood = np.ones_like(theta_range)
        for resp in responses:
            a, b, g = resp['a'], resp['b'], resp['g']
            u = resp.get('u', 1.0)
            answer = resp['answer']
            p = np.array([probability_3pl(theta_val, a, b, g, u) for theta_val in theta_range])
            p = np.clip(p, 1e-10, 1 - 1e-10)
            if answer == 1:
                likelihood *= p
            else:
                likelihood *= (1 - p)

        # Calculate posterior
        posterior = likelihood * weights
        posterior_sum = np.sum(posterior)
        if posterior_sum > 0:
            posterior = posterior / posterior_sum
        else:
            # Fallback jika posterior sum = 0
            posterior = weights / np.sum(weights)

        # EAP estimate: expected value of posterior distribution
        theta_eap = np.sum(theta_range * posterior)

        # Absolute theta bounds
        theta_eap = max(-6, min(6, theta_eap))

        # Calculate SE_EAP using variance of posterior
        variance = np.sum(((theta_range - theta_eap)**2) * posterior)
        se_eap = np.sqrt(variance)

        return theta_eap, se_eap
    except (OverflowError, ValueError, ZeroDivisionError):
        return prior_mean, prior_sd

def expected_fisher_information(a, b, g, u, responses=None):
    """Calculate Expected Fisher Information (EFI) for 3PL model with EAP"""
    try:
        # Grid theta yang sinkron dengan EAP
        theta_grid = np.linspace(-6, 6, 1001)
        
        if not responses:
            # Jika belum ada respons, gunakan prior N(0,2)
            prior = np.exp(-0.5 * (theta_grid / 2)**2)
            prior = prior / np.sum(prior)
            
            # Hitung EFI berdasarkan prior
            efi = 0
            for theta_val, weight in zip(theta_grid, prior):
                info = information_3pl(theta_val, a, b, g, u)
                efi += info * weight
            return efi
        
        # Jika sudah ada respons, gunakan posterior dari EAP
        # Prior distribution: N(0,2) - SINKRON dengan EAP
        prior = np.exp(-0.5 * (theta_grid / 2)**2)
        prior = prior / np.sum(prior)
        
        # Hitung likelihood untuk setiap theta di grid
        likelihood = np.ones_like(theta_grid)
        
        for response in responses:
            a_resp, b_resp, g_resp = response['a'], response['b'], response['g']
            u_resp = response.get('u', 1.0)
            answer = response['answer']
            
            p = np.array([probability_3pl(theta_val, a_resp, b_resp, g_resp, u_resp) 
                         for theta_val in theta_grid])
            p = np.clip(p, 1e-10, 1 - 1e-10)
            
            if answer == 1:
                likelihood *= p
            else:
                likelihood *= (1 - p)
        
        # Hitung posterior distribution
        posterior = likelihood * prior
        posterior_sum = np.sum(posterior)
        if posterior_sum > 0:
            posterior = posterior / posterior_sum
        else:
            posterior = prior
        
        # Hitung Expected Fisher Information
        efi = 0
        for theta_val, weight in zip(theta_grid, posterior):
            info = information_3pl(theta_val, a, b, g, u)
            efi += info * weight
            
        return efi
        
    except (OverflowError, ValueError, ZeroDivisionError):
        return 0.0

def select_next_item_mi(theta, used_item_ids, item_bank, responses=None):
    """Select next item using Maximum Fisher Information (MI) based on MAP theta"""
    log_select_next_item()  # Log performance
    try:
        available_items = [item for item in item_bank if item['id'] not in used_item_ids]
        if not available_items:
            return None

        # Get b values for forcing logic
        b_values = np.array([item['b'] for item in item_bank])
        b_values_valid = b_values[(b_values >= -6) & (b_values <= 6)]
        b_max = np.max(b_values_valid) if len(b_values_valid) > 0 else 6.0
        b_min = np.min(b_values_valid) if len(b_values_valid) > 0 else -6.0
        margin = max(0.5, 0.1 * (b_max - b_min))

        # Forcing logic: only if b_max/b_min item BELUM PERNAH diberikan
        # Cek apakah item b_max sudah pernah diberikan
        b_max_given = any(
            np.isclose(item['b'], b_max, atol=0.001) and -6 <= item['b'] <= 6
            for item in item_bank if item['id'] in used_item_ids
        )
        # Cek apakah item b_min sudah pernah diberikan
        b_min_given = any(
            np.isclose(item['b'], b_min, atol=0.001) and -6 <= item['b'] <= 6
            for item in item_bank if item['id'] in used_item_ids
        )

        # Jika theta sangat tinggi dan item b_max belum pernah diberikan, paksa pilih b_max
        if theta > b_max - margin and not b_max_given:
            logger.info(f"Forcing b_max triggered: theta={theta:.3f} > {b_max:.3f} - {margin:.3f} = {b_max - margin:.3f}, b_max_given={b_max_given}")
            for item in available_items:
                if np.isclose(item['b'], b_max, atol=0.001) and -6 <= item['b'] <= 6:
                    logger.info(f"Forcing b_max item: {item['id']} (b={item['b']:.3f}) for theta={theta:.3f}")
                    return item

        # Jika theta sangat rendah dan item b_min belum pernah diberikan, paksa pilih b_min
        if theta < b_min + margin and not b_min_given:
            logger.info(f"Forcing b_min triggered: theta={theta:.3f} < {b_min:.3f} + {margin:.3f} = {b_min + margin:.3f}, b_min_given={b_min_given}")
            for item in available_items:
                if np.isclose(item['b'], b_min, atol=0.001) and -6 <= item['b'] <= 6:
                    logger.info(f"Forcing b_min item: {item['id']} (b={item['b']:.3f}) for theta={theta:.3f}")
                    return item

        # Default: pilih item dengan Maximum Fisher Information (MI) pada theta MAP
        max_info = -1
        best_item = None
        for item in available_items:
            # Calculate Fisher Information at current theta (MAP estimate)
            info = information_3pl(theta, item['a'], item['b'], item['g'], item['u'])
            if info > max_info:
                max_info = info
                best_item = item
        
        if best_item:
            logger.info(f"Selected item {best_item['id']} with MI={max_info:.3f} at theta={theta:.3f}")
        
        return best_item
    except (ValueError, TypeError):
        return available_items[0] if available_items else None

def calculate_score(theta):
    """Menghitung skor dengan rumus (100+15) * theta berbasis IQ"""
    log_calculate_score()  # Log performance
    try:
        # Formula berbasis IQ: 100 + (15 * theta)
        # Dimana theta 0 = IQ 100 (rata-rata), theta 1 = IQ 115, theta -1 = IQ 85
        final_score = 100 + (15 * theta)
        return final_score
    except (ValueError, TypeError):
        return 100.0  # Default IQ 100 jika error

def check_stopping_criteria(responses, se_eap, used_item_ids, max_items=30, se_threshold=0.25):
    """Check if test should stop based on criteria"""
    log_stopping_criteria()  # Log performance
    try:
        # Get b values from item bank for min/max detection (filter -6 <= b <= 6)
        b_values = np.array([item['b'] for item in ITEM_BANK])
        b_values_valid = b_values[(b_values >= -6) & (b_values <= 6)]
        b_max = np.max(b_values_valid) if len(b_values_valid) > 0 else 6.0
        b_min = np.min(b_values_valid) if len(b_values_valid) > 0 else -6.0
        
        logger.info(f"Stopping criteria check: responses={len(responses)}, se_eap={se_eap:.3f}, used_items={len(used_item_ids)}, b_max={b_max:.3f}, b_min={b_min:.3f}")
        
        # Check if participant got maximum difficulty item (b_max) correct
        b_max_responses = []
        for resp in responses:
            if np.isclose(resp.get('b', 0), b_max, atol=0.001) and -6 <= resp.get('b', 0) <= 6:
                b_max_responses.append(f"item_b={resp.get('b', 0):.3f}, answer={resp.get('answer')}")
        
        has_b_max = any(
            np.isclose(resp.get('b', 0), b_max, atol=0.001) and 
            -6 <= resp.get('b', 0) <= 6 and 
            resp.get('answer') == 1
            for resp in responses
        )
        
        # Check if participant got minimum difficulty item (b_min) incorrect
        b_min_responses = []
        for resp in responses:
            if np.isclose(resp.get('b', 0), b_min, atol=0.001) and -6 <= resp.get('b', 0) <= 6:
                b_min_responses.append(f"item_b={resp.get('b', 0):.3f}, answer={resp.get('answer')}")
                
        has_b_min = any(
            np.isclose(resp.get('b', 0), b_min, atol=0.001) and 
            -6 <= resp.get('b', 0) <= 6 and 
            resp.get('answer') == 0
            for resp in responses
        )
        
        logger.info(f"B_max responses: {b_max_responses}, has_b_max_correct: {has_b_max}")
        logger.info(f"B_min responses: {b_min_responses}, has_b_min_incorrect: {has_b_min}")
        
        # SE threshold reached (need at least 10 items)
        if len(responses) >= 10 and se_eap <= se_threshold:
            logger.info(f"Stopping: SE threshold reached ({se_eap:.3f} <= {se_threshold})")
            return True, "SE_EAP mencapai 0.25 dengan minimal 10 soal"
        # Maximum items reached
        elif len(responses) >= max_items:
            logger.info(f"Stopping: Max items reached ({len(responses)} >= {max_items})")
            return True, "Mencapai maksimal 30 soal"
        # All items have been used
        elif len(used_item_ids) >= len(ITEM_BANK):
            logger.info(f"Stopping: All items used ({len(used_item_ids)} >= {len(ITEM_BANK)})")
            return True, "Semua item telah digunakan"
        # Participant got maximum difficulty item correct
        elif has_b_max:
            logger.info(f"Stopping: Got b_max item correct (b={b_max:.3f})")
            return True, f"Peserta sudah mendapat soal dengan b maksimum (paling sulit): {b_max:.3f}"
        # Participant got minimum difficulty item incorrect
        elif has_b_min:
            logger.info(f"Stopping: Got b_min item incorrect (b={b_min:.3f})")
            return True, f"Peserta sudah mendapat soal dengan b minimum (paling mudah): {b_min:.3f}"
        # Continue test
        else:
            logger.info(f"Continuing: No stopping criteria met")
            return False, "Continuing"
    except (ValueError, TypeError) as e:
        logger.error(f"Error in stopping criteria: {str(e)}")
        return False, "Continuing"

# API Routes
@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'version': API_VERSION,
        'timestamp': datetime.now().isoformat(),
        'service': 'CAT Flask API'
    })

@app.route('/api/estimate-theta', methods=['POST'])
def estimate_theta():
    """Estimate theta using MAP method for real-time estimation during test"""
    log_api_request('estimate_theta')  # Log performance
    try:
        data = request.get_json()
        responses = data.get('responses', [])
        theta_old = data.get('theta_old', 0.0)  # Get previous theta from request
        
        if not responses:
            return jsonify({'error': 'No responses provided'}), 400
        
        # Validate response format (support API and GUI)
        parsed_responses = []
        for resp in responses:
            if 'item' in resp:
                item = resp['item']
                if not all(key in item for key in ['a', 'b', 'g']) or 'answer' not in resp:
                    return jsonify({'error': 'Invalid GUI response format. Required: item.a, item.b, item.g, answer'}), 400
                parsed_responses.append({
                    'a': item['a'],
                    'b': item['b'],
                    'g': item['g'],
                    'u': item.get('u', 1.0),
                    'answer': resp['answer']
                })
            else:
                if not all(key in resp for key in ['a', 'b', 'g', 'answer']):
                    return jsonify({'error': 'Invalid API response format. Required keys: a, b, g, answer'}), 400
                parsed_responses.append({
                    'a': resp['a'],
                    'b': resp['b'],
                    'g': resp['g'],
                    'u': resp.get('u', 1.0),
                    'answer': resp['answer']
                })

        # Use MAP for real-time estimation during test
        theta_map, se_map = estimate_theta_map(parsed_responses, theta_old=theta_old)

        return jsonify({
            'theta': float(theta_map),
            'se': float(se_map),
            'method': 'MAP',
            'n_responses': len(parsed_responses),
            'theta_old': float(theta_old)
        })
        
    except Exception as e:
        logger.error(f"Error in estimate_theta: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/select-item', methods=['POST'])
def select_item():
    """Select next item using MI (Maximum Fisher Information) method"""
    log_api_request('select_item')  # Log performance
    try:
        data = request.get_json()
        theta = data.get('theta', 0.0)
        used_item_ids = data.get('used_item_ids', [])
        responses = data.get('responses', [])
        
        # Get item bank
        item_bank = ITEM_BANK
        
        # Select next item using Maximum Fisher Information
        next_item = select_next_item_mi(theta, used_item_ids, item_bank, responses)
        
        if not next_item:
            return jsonify({'error': 'No items available'}), 404
        
        # Calculate probability, information, and EFI (for compatibility)
        probability = probability_3pl(theta, next_item['a'], next_item['b'], next_item['g'], next_item['u'])
        information = information_3pl(theta, next_item['a'], next_item['b'], next_item['g'], next_item['u'])
        efi = expected_fisher_information(next_item['a'], next_item['b'], next_item['g'], next_item['u'], responses)
        
        return jsonify({
            'item': next_item,
            'probability': float(probability),
            'information': float(information),
            'fisher_information': float(information),  # MI = Fisher Information at theta
            'expected_fisher_information': float(efi),  # Keep for compatibility
            'method': 'MI',
            'available_items': len(item_bank) - len(used_item_ids)
        })
        
    except Exception as e:
        logger.error(f"Error in select_item: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/calculate-score', methods=['POST'])
def calculate_score_endpoint():
    """Calculate score from theta"""
    log_api_request('calculate_score')  # Log performance
    try:
        data = request.get_json()
        theta = data.get('theta', 0.0)
        
        score = calculate_score(theta)
        
        return jsonify({
            'score': float(score),
            'theta': float(theta),
            'scale': 'IQ-based (100 + 15*theta)'
        })
        
    except Exception as e:
        logger.error(f"Error in calculate_score: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/stopping-criteria', methods=['POST'])
def stopping_criteria():
    """Check stopping criteria"""
    log_api_request('stopping_criteria')  # Log performance
    try:
        data = request.get_json()
        responses = data.get('responses', [])
        se_eap = data.get('se_eap', 1.0)
        used_item_ids = data.get('used_item_ids', [])
        max_items = data.get('max_items', 30)
        se_threshold = data.get('se_threshold', 0.25)
        
        should_stop, reason = check_stopping_criteria(
            responses, se_eap, used_item_ids, max_items, se_threshold
        )
        
        return jsonify({
            'should_stop': should_stop,
            'reason': reason,
            'items_administered': len(used_item_ids),
            'max_items': max_items,
            'current_se': float(se_eap),
            'se_threshold': se_threshold
        })
        
    except Exception as e:
        logger.error(f"Error in stopping_criteria: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/debug-stopping', methods=['POST'])
def debug_stopping():
    """Debug endpoint to test stopping criteria manually"""
    try:
        data = request.get_json()
        responses = data.get('responses', [])
        se_eap = data.get('se_eap', 1.0)
        used_item_ids = data.get('used_item_ids', [])
        
        # Create some test scenarios
        test_scenarios = []
        
        # Scenario 1: High theta user with b_max item answered correctly
        if ITEM_BANK:
            b_max_item = max(ITEM_BANK, key=lambda x: x['b'])
            test_responses_high = [
                {
                    'a': b_max_item['a'],
                    'b': b_max_item['b'], 
                    'g': b_max_item['g'],
                    'answer': 1  # Correct answer
                }
            ]
            should_stop_high, reason_high = check_stopping_criteria(test_responses_high, 0.3, [b_max_item['id']])
            test_scenarios.append({
                'scenario': 'High ability - b_max correct',
                'responses': test_responses_high,
                'should_stop': should_stop_high,
                'reason': reason_high
            })
            
            # Scenario 2: Low theta user with b_min item answered incorrectly  
            b_min_item = min(ITEM_BANK, key=lambda x: x['b'])
            test_responses_low = [
                {
                    'a': b_min_item['a'],
                    'b': b_min_item['b'],
                    'g': b_min_item['g'], 
                    'answer': 0  # Incorrect answer
                }
            ]
            should_stop_low, reason_low = check_stopping_criteria(test_responses_low, 0.3, [b_min_item['id']])
            test_scenarios.append({
                'scenario': 'Low ability - b_min incorrect',
                'responses': test_responses_low,
                'should_stop': should_stop_low,
                'reason': reason_low
            })
            
            # Scenario 3: SE threshold reached
            should_stop_se, reason_se = check_stopping_criteria(test_responses_high * 10, 0.2, list(range(10)))
            test_scenarios.append({
                'scenario': 'SE threshold (10 items, SE=0.2)',
                'should_stop': should_stop_se,
                'reason': reason_se
            })
        
        # Test actual provided data
        if responses:
            should_stop_actual, reason_actual = check_stopping_criteria(responses, se_eap, used_item_ids)
            test_scenarios.append({
                'scenario': 'Actual provided data',
                'responses_count': len(responses),
                'se_eap': se_eap,
                'used_items_count': len(used_item_ids),
                'should_stop': should_stop_actual,
                'reason': reason_actual
            })
            
        return jsonify({
            'item_bank_info': {
                'total_items': len(ITEM_BANK),
                'b_max': max(item['b'] for item in ITEM_BANK) if ITEM_BANK else None,
                'b_min': min(item['b'] for item in ITEM_BANK) if ITEM_BANK else None
            },
            'test_scenarios': test_scenarios,
            'debug_info': 'Use this endpoint to test stopping criteria logic'
        })
        
    except Exception as e:
        logger.error(f"Error in debug_stopping: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/item-bank', methods=['GET'])
def get_item_bank():
    """Get item bank information"""
    try:
        return jsonify({
            'items': ITEM_BANK,
            'count': len(ITEM_BANK),
            'parameters': ['a', 'b', 'g', 'u'],
            'model': '3PL',
            'source': 'Parameter_Item_IST.csv'
        })
        
    except Exception as e:
        logger.error(f"Error in get_item_bank: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/final-score', methods=['POST'])
def final_score():
    """Calculate final score using EAP method"""
    log_final_scoring()  # Log performance
    try:
        data = request.get_json()
        responses = data.get('responses', [])
        
        if not responses:
            return jsonify({'error': 'No responses provided'}), 400
        
        # Validate response format (support API and GUI)
        parsed_responses = []
        for resp in responses:
            if 'item' in resp:
                item = resp['item']
                if not all(key in item for key in ['a', 'b', 'g']) or 'answer' not in resp:
                    return jsonify({'error': 'Invalid GUI response format. Required: item.a, item.b, item.g, answer'}), 400
                parsed_responses.append({
                    'a': item['a'],
                    'b': item['b'],
                    'g': item['g'],
                    'u': item.get('u', 1.0),
                    'answer': resp['answer']
                })
            else:
                if not all(key in resp for key in ['a', 'b', 'g', 'answer']):
                    return jsonify({'error': 'Invalid API response format. Required keys: a, b, g, answer'}), 400
                parsed_responses.append({
                    'a': resp['a'],
                    'b': resp['b'],
                    'g': resp['g'],
                    'u': resp.get('u', 1.0),
                    'answer': resp['answer']
                })

        # Use EAP for final scoring
        theta_eap, se_eap = estimate_theta_eap(parsed_responses)
        
        # Calculate final score
        final_score = calculate_score(theta_eap)

        return jsonify({
            'theta': float(theta_eap),
            'se_eap': float(se_eap),
            'final_score': float(final_score),
            'method': 'EAP',
            'n_responses': len(parsed_responses)
        })
        
    except Exception as e:
        logger.error(f"Error in final_score: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/test-calculation', methods=['POST'])
def test_calculation():
    """Test endpoint for debugging calculations"""
    try:
        if not ITEM_BANK:
            return jsonify({'error': 'No items loaded from CSV file'}), 400
            
        data = request.get_json()
        
        # Use first 2 items from actual CSV data for testing
        test_responses = [
            {
                'a': ITEM_BANK[0]['a'], 
                'b': ITEM_BANK[0]['b'], 
                'g': ITEM_BANK[0]['g'], 
                'answer': 1
            },
            {
                'a': ITEM_BANK[1]['a'], 
                'b': ITEM_BANK[1]['b'], 
                'g': ITEM_BANK[1]['g'], 
                'answer': 0
            }
        ]
        
        # Estimate theta using MAP (for during test)
        theta_map, se_map = estimate_theta_map(test_responses)
        
        # Estimate theta using EAP (for final scoring)
        theta_eap, se_eap = estimate_theta_eap(test_responses)
        
        # Select next item using MI
        used_ids = [ITEM_BANK[0]['id'], ITEM_BANK[1]['id']]
        next_item = select_next_item_mi(theta_map, used_ids, ITEM_BANK, test_responses)
        
        # Calculate probability, information, and EFI
        if next_item:
            probability = probability_3pl(theta_map, next_item['a'], next_item['b'], next_item['g'], next_item['u'])
            information = information_3pl(theta_map, next_item['a'], next_item['b'], next_item['g'], next_item['u'])
            efi = expected_fisher_information(next_item['a'], next_item['b'], next_item['g'], next_item['u'], test_responses)
        else:
            probability = 0.0
            information = 0.0
            efi = 0.0
        
        # Calculate score using EAP
        score = calculate_score(theta_eap)
        
        # Check stopping criteria
        should_stop, reason = check_stopping_criteria(test_responses, se_map, used_ids)
        
        return jsonify({
            'test_data': {
                'responses': test_responses,
                'theta_map': float(theta_map),
                'se_map': float(se_map),
                'theta_eap': float(theta_eap),
                'se_eap': float(se_eap),
                'next_item': next_item,
                'probability': float(probability),
                'information': float(information),
                'expected_fisher_information': float(efi),
                'score': float(score),
                'should_stop': should_stop,
                'stop_reason': reason
            },
            'csv_status': f'✓ Loaded {len(ITEM_BANK)} items from CSV',
            'status': 'Test calculation completed successfully'
        })
        
    except Exception as e:
        logger.error(f"Error in test_calculation: {str(e)}")
        return jsonify({'error': 'Internal server error'}), 500

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    log_start_cat()  # Log start CAT system
    logger.info(f"Starting CAT Flask API Server v{API_VERSION}")
    logger.info(f"Server will run at: http://{HOST}:{PORT}")
    logger.info(f"Item bank loaded: {len(ITEM_BANK)} items")
    logger.info("Available endpoints:")
    logger.info("  GET  /health - Health check")
    logger.info("  POST /api/estimate-theta - Estimate theta using MAP (real-time)")
    logger.info("  POST /api/select-item - Select next item using MI")
    logger.info("  POST /api/calculate-score - Calculate score from theta")
    logger.info("  POST /api/final-score - Calculate final score using EAP")
    logger.info("  POST /api/stopping-criteria - Check stopping criteria")
    logger.info("  GET  /api/item-bank - Get item bank information")
    logger.info("  POST /api/test-calculation - Test calculation endpoint")
    
    try:
        app.run(
            host=HOST,
            port=PORT,
            debug=False,
            threaded=True
        )
    except Exception as e:
        logger.error(f"Failed to start server: {str(e)}")
        log_end_cat()  # Log end CAT system
        exit(1)
    finally:
        log_end_cat()  # Log end CAT system
