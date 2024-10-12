#!/bin/bash

git branch | grep 'feature/2023' | xargs git branch -d
