#!/bin/bash

set -u

# Ensure coverage directory exists so artifact upload doesn't fail on missing directory
mkdir -p coverage/maarch-courrier

COBERTURA_FILE="coverage/maarch-courrier/cobertura-coverage.xml"
ENV_OUT="src/frontend/test/karmajs/job-coverage.env"

if [ ! -f "$COBERTURA_FILE" ]; then
  echo "Cobertura file not found at $COBERTURA_FILE. Setting coverage to 0%."
  echo "Coverage: 0%"
  echo "CI_JOB_COVERAGE=0" > "$ENV_OUT"
  # Create minimal Cobertura and JUnit XML files so artifacts upload doesn't fail
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><coverage line-rate=\"0\" branch-rate=\"0\" version=\"1.9\" timestamp=\"$(date +%s)\"><sources/><packages/></coverage>" > "$COBERTURA_FILE"
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><testsuite name=\"karma\" tests=\"0\" failures=\"0\" errors=\"0\" skipped=\"0\" time=\"0\"></testsuite>" > "coverage/maarch-courrier/junit-coverage.xml"
  exit 0
fi

# Extract all line-rate values from the Cobertura coverage XML file
values=$(grep -oP 'line-rate="\K[0-9.]+(?=")' "$COBERTURA_FILE" || true)

# Convert the extracted values into an array
values_array=($values)

# Initialize sum and count variables
sum=0
count=0

# Iterate over each line-rate value and sum them up
for value in "${values_array[@]}"; do
    sum=$(echo "$sum + $value" | bc)
    count=$((count + 1))
done

# Calculate the average coverage percentage
if [ $count -gt 0 ]; then
    average=$(echo "$sum / $count" | bc -l)
    coverage_percentage=$(printf "%.2f" $(echo "$average * 100" | bc))
    # Replace comma with a dot if necessary (ensuring proper decimal format)
    coverage_percentage=$(echo $coverage_percentage | sed 's/,/./g')
    echo "Coverage: $coverage_percentage%"
    # Export the coverage percentage as a GitLab CI environment variable
    echo "CI_JOB_COVERAGE=$coverage_percentage" > "$ENV_OUT"
else
    echo "Coverage: 0%"
    echo "CI_JOB_COVERAGE=0" > "$ENV_OUT"
fi
