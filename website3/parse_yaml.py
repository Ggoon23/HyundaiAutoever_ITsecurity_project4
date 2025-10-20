#!/usr/bin/env python3
"""
PyYAML 역직렬화 RCE 취약점 (CVE-2020-14343)
WARNING: 이 스크립트는 교육 목적으로만 사용하세요!
"""

import sys
import json
import yaml

def parse_yaml_file(file_path):
    """
    YAML 파일을 파싱합니다.
    WARNING: yaml.load()를 사용하여 안전하지 않은 역직렬화를 수행합니다.
    이는 CVE-2020-14343 취약점을 재현하기 위한 것입니다.
    """
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            # VULNERABLE CODE: yaml.load() with Loader=yaml.Loader
            # 공격자는 악의적인 YAML 페이로드로 RCE를 수행할 수 있습니다
            data = yaml.load(f, Loader=yaml.Loader)

            # Convert to JSON for PHP consumption
            print(json.dumps(data, ensure_ascii=False))

    except Exception as e:
        error_response = {
            'error': str(e),
            'message': 'YAML 파싱 중 오류 발생'
        }
        print(json.dumps(error_response, ensure_ascii=False))
        sys.exit(1)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Usage: parse_yaml.py <file_path>'}))
        sys.exit(1)

    file_path = sys.argv[1]
    parse_yaml_file(file_path)
